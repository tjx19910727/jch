<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 9:32
 */

namespace app\AppFactory\Notice;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\Exceptions\NoticeException;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Support\Validate\Notice\VNotice;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Email\EmailConfigTrait;
use app\AppFactory\Kernel\Traits\Email\EmailTemplateLogTrait;
use app\AppFactory\Kernel\Traits\Email\EmailTemplateTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Kernel\Traits\Wx\WxTemplateLogTrait;
use app\AppFactory\Kernel\Traits\Wx\WxTemplateTrait;

class NoticeBaseClient extends BaseClient
{
    use EmailConfigTrait, EmailTemplateTrait, EmailTemplateLogTrait;
    use WxOfficialTrait, WxTemplateTrait, WxTemplateLogTrait;
    use AuthManagerMachineTrait;

    protected $template;
    protected $receiver;
    /**
     * @var array
     * 参数                     必传               说明
     * ao_id                     是        组织架构ID
     * sendType                  是        发送类型，1：微信，2：邮件，默认1.微信
     * templateType              是        消息类型，与后台配置的类型一致，在线状态：online，库存不足：understock, 故障通知：fault，销售通知：sale
     * config                    否        发件方配置，可不传，不传时以ao_id去查询对应sendType的配置数据
     * replaceData               否        需要替换的数据，如：machine_id,machine_name,errorCode,online,last_online_time,country,state,city,regions,street,floor,now,date,time等，详情见替换字符表
     * template                  否        消息模板，可不传，不传时以配置ID和消息类型去查询已启用的最新消息模板
     */
    protected $config = [
        "ao_id" => "",
        "sendType" => 1,
        "templateType" => "",
        "receiver" => [],
    ];

    /**
     * NoticeBaseClient constructor.
     * @param ServiceContainer $app
     * @throws NoticeException
     */
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->config = $app->getConfig();
        actionLog($this->config, '接收数据');
        try {
            validate(VNotice::class)->scene("getConfig")->check($this->config);
        } catch (\Exception $e) {
            actionLog($e->getMessage(), '数据检验结果');
//            throw new NoticeException($e->getMessage());
        }
        $this->getConfig();
        if (!isset($this->config['config']) || !$this->config['config']) {
            actionLog($this->config, '查无配置信息');
//            throw new NoticeException($this->lang("VNotice.config_require"));
        }
        $this->getTemplate();
        if (!isset($this->config['template']) || !$this->config['template']) {
            actionLog($this->config, '查无消息模板信息');
//            throw new NoticeException($this->lang("VNotice.template_require"));
        }
        $this->replaceBodyParams();
        $this->getReceiver();
        if (!isset($this->config['receiver']) || !$this->config['receiver']) {
            actionLog($this->config, '查无收件人信息');
//            throw new NoticeException($this->lang("VNotice.receiver_require"));
        }
    }

    /**
     * 检查发件方配置，微信公众号配置、邮件发件方配置
     */
    protected function getConfig()
    {
        if (!isset($this->config['config']) || !$this->config['config']) {
            if ($this->config['sendType'] == 1) {
                $this->config['config'] = $this->getWxOfficialFind(['ao_id' => $this->config['ao_id'], 'status' => 1],
                    'id,gh_id,wx_name,app_id,secret,token,aes_key,ao_id,creator', 'update_time desc');
                if ($this->config['config']) $this->config['config'] = $this->config['config']->toArray();
                $this->config['config'];
            }
            if ($this->config['sendType'] == 2) {
                $this->config['config'] = $this->getEmailConfigFind(['ao_id' => $this->config['ao_id'], 'status' => 1],
                    'ec_id,host,username,authCode,sendEmail,nickname,replyMail,replyNickname,isHtml,ao_id,creator', 'update_time desc');
                if ($this->config['config']) $this->config['config'] = $this->config['config']->toArray();
                $this->config['config'];
            }
//            $this->r(100,$this->lang("VNotice.config_require"))->send();
//            return 0;
        }
//        return 1;
    }

    /**
     * 获取消息模板
     */
    protected function getTemplate()
    {
        if ($this->config['config'] && (!isset($this->config['template']) || !$this->config['template'])) {
            if ($this->config['sendType'] == 1) {
                $this->config['template'] = $this->getWxTemplateFind(['wx_id' => $this->config['config']['id'], 'template_type' => $this->config['templateType'], 'status' => 1],
                    'wt_id,wx_id,template_name,template_type,template_id,url,miniprogram,body,ao_id', 'update_time desc');
            }
            if ($this->config['sendType'] == 2) {
                $this->config['template'] = $this->getEmailTemplateFind(['ec_id' => $this->config['config']['ec_id'], 'template_type' => $this->config['templateType'], 'status' => 1],
                    'et_id,ec_id,CC,BCC,subject,body,altBody,attachment,template_type,ao_id','update_time desc');
            }
            if (isset($this->config['template']) && $this->config['template']) $this->config['template'] = $this->config['template']->toArray();
//            $this->r(100,$this->lang("VNotice.template_require"))->send();
//            return 0;
        }
//        return 1;
    }

    /**
     * 获取收件人信息
     */
    protected function getReceiver()
    {
        if (!isset($this->config['receiver']) || !$this->config['receiver']) {
            if (isset($this->config['m_id']) && $this->config['m_id']) {
                $where['amm.m_id'] = $this->config['m_id'];
                $where['am.status'] = 1;
                if ($this->config['sendType'] == 1) {
                    $where[] = ['am.wx_notice', 'like', "%" . $this->config['templateType'] . "%"];
                    $where[] = function ($query) {
                        $query->where("am.openid is not null");
                    };
                }
                if ($this->config['sendType'] == 2) {
                    $where[] = ['am.email_notice', 'like', "%" . $this->config['templateType'] . "%"];
                    $where[] = function ($query) {
                        $query->where("am.email is not null");
                    };
                }
                $this->config['receiver'] = $this->getAmmJoinAmList($where, 'am.manager_id,am.nickname,am.ao_id,am.email,am.openid');
                if ($this->config['receiver']) $this->config['receiver'] = $this->config['receiver']->toArray();
                actionLog($this->getLS(), '获取收件人SQL');
            }
        }
    }

    /**
     * 替换消息主体参数
     */
    protected function replaceBodyParams()
    {
        if (isset($this->config['template'])) {
            if (isset($this->config['replaceData'])) {
                foreach ($this->config['replaceData'] as $key => $value) {
                    if (strpos($this->config['template']['body'], '{{' . $key . '}}') !== false) {
                        $this->config['template']['body'] = str_replace('{{' . $key . '}}', $value, $this->config['template']['body']);
                    }
                }
            }
            if (strpos($this->config['template']['body'], '{{now}}') !== false) $this->config['template']['body'] = str_replace('{{now}}', date("Y-m-d H:i:s"), $this->config['template']['body']);
            if (strpos($this->config['template']['body'], '{{date}}') !== false) $this->config['template']['body'] = str_replace('{{date}}', date("Y-m-d"), $this->config['template']['body']);
            if (strpos($this->config['template']['body'], '{{time}}') !== false) $this->config['template']['body'] = str_replace('{{time}}', date("H:i:s"), $this->config['template']['body']);
            if (strpos($this->config['template']['body'], '{{Y}}') !== false) $this->config['template']['body'] = str_replace('{{Y}}', date("Y"), $this->config['template']['body']);
            if (strpos($this->config['template']['body'], '{{m}}') !== false) $this->config['template']['body'] = str_replace('{{m}}', date("m"), $this->config['template']['body']);
            if (strpos($this->config['template']['body'], '{{d}}') !== false) $this->config['template']['body'] = str_replace('{{d}}', date("d"), $this->config['template']['body']);
            if (strpos($this->config['template']['body'], '{{H}}') !== false) $this->config['template']['body'] = str_replace('{{H}}', date("H"), $this->config['template']['body']);
            if (strpos($this->config['template']['body'], '{{i}}') !== false) $this->config['template']['body'] = str_replace('{{i}}', date("i"), $this->config['template']['body']);
            if (strpos($this->config['template']['body'], '{{s}}') !== false) $this->config['template']['body'] = str_replace('{{s}}', date("s"), $this->config['template']['body']);
            if (is_string($this->config['template']['body']) && $this->config['sendType'] == 1) $this->config['template']['body'] = json_decode($this->config['template']['body'], true);
        }
    }
}