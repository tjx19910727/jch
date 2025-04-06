<?php
declare (strict_types = 1);

namespace app\command;

use app\http\controller\redis\MicroPay;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class Payment extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('payment')
            ->addArgument("payMethod",Argument::OPTIONAL,'payment pay method')
            ->addArgument("microSec",Argument::OPTIONAL,'handle time',1000)
            ->setDescription('the payment command');
    }

    /**
     * 支付守护进程入口
     * command             命令行
     *      php think payment [payMethod]
     * payMethod           支付方式参数名
     *      microPay            反扫支付
     * microSec            程序休眠时间
     *      1000                1000毫秒
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $result = '未定义命令类型';
        try {
            $argument = $input->getArguments();
            if (isset($argument['payMethod'])) {
                if ($argument['payMethod'] == "microPay") {
                    $app = new MicroPay();
                    $app->microSec = $argument['microSec'];
                    $result = $app->queryMicroPay();
                }
            }// 指令输出
            $output->writeln($result);
        } catch (\Exception $e) {
            actionException($e,1);
//             指令输出
            $output->writeln($e->getMessage());
        }
    }
}
