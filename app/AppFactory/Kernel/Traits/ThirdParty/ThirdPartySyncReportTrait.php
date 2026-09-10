<?php
/**
 * A 层写点实时上报（无触发器方案的实时通道）。
 *
 * 供各业务写点复用：在 goods / machine_channel 的集中写方法完成后调用
 * reportGoodsChangedToThirdParty() / reportMachineChangedToThirdParty()，
 * 将 ao_id=17（核心主体）的变化对象聚合进 third_party_sync_dirty，
 * 后续仍由 third_party_sync dispatch → api callback trigger_send 推送微程。
 *
 * 约束与设计：
 *  - 只写本地 dirty（upsert、version+1），不访问第三方网络；异常静默，
 *    同步设施故障绝不阻塞商品/货道/库存等业务（与既有设计边界一致）；
 *  - 上报本身带 ao_id=17 过滤，非核心主体的对象不会写入 dirty；
 *  - 商品变化会联动装载该商品的 ao_id=17 设备（整机快照含商品资料），
 *    覆盖 updateGoods 内直改 machine_channel 名称/图片等绕过货道收口的情况；
 *  - 若调用方在业务事务内执行写方法，upsert 使用同一 Db 连接，随事务一起
 *    提交/回滚（与触发器语义一致）。
 */
namespace app\AppFactory\Kernel\Traits\ThirdParty;

use app\AppFactory\Kernel\Service\Api\ThirdPartyProductSyncService;
use think\facade\Db;

trait ThirdPartySyncReportTrait
{
    /**
     * 需要同步的核心主体组织 ID（与触发器、扫描服务一致）。
     *
     * @var int
     */
    protected $thirdPartySyncCoreAoId = 17;

    /**
     * 上报核心商品变化（可选联动装载该商品的设备）。
     *
     * @param string|int $gId       商品 g_id
     * @param string     $operation upsert|delete（物理删除前请传入 knownAoId）
     * @param int|null   $knownAoId 已知的商品 ao_id（行已删除等无法回查时传入）
     * @return void
     */
    protected function reportGoodsChangedToThirdParty($gId, $operation = 'upsert', $knownAoId = null)
    {
        $gId = intval($gId);
        if ($gId <= 0) {
            return;
        }
        try {
            $aoId = $knownAoId === null
                ? intval(Db::name('goods')->where('g_id', $gId)->value('ao_id'))
                : intval($knownAoId);
            if ($aoId !== (int)$this->thirdPartySyncCoreAoId) {
                return;
            }

            $sync = new ThirdPartyProductSyncService();
            $sync->enqueueGoods($gId, $operation);

            // 联动：装载该商品的 ao_id=17 设备同样需要推整机快照。
            $machineIds = Db::name('machine_channel')->alias('mc')
                ->join('machine m', 'm.machine_id = mc.machine_id AND m.ao_id = ' . (int)$this->thirdPartySyncCoreAoId)
                ->where('mc.g_id', $gId)
                ->where('mc.machine_id', '<>', '')
                ->distinct(true)
                ->column('mc.machine_id');
            foreach ($machineIds as $machineId) {
                $sync->enqueueMachine($machineId);
            }
        } catch (\Throwable $e) {
            // 同步上报失败不影响商品/货道业务。
        }
    }

    /**
     * 上报设备（货道载体）变化。
     *
     * @param string $machineId 设备编号（machine.machine_id）
     * @return void
     */
    protected function reportMachineChangedToThirdParty($machineId)
    {
        $machineId = trim((string)$machineId);
        if ($machineId === '') {
            return;
        }
        try {
            $aoId = intval(Db::name('machine')->where('machine_id', $machineId)->value('ao_id'));
            if ($aoId !== (int)$this->thirdPartySyncCoreAoId) {
                return;
            }
            (new ThirdPartyProductSyncService())->enqueueMachine($machineId);
        } catch (\Throwable $e) {
            // 同步上报失败不影响货道/库存业务。
        }
    }
}
