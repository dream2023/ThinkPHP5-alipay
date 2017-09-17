<?php

namespace app\common\controller;


use think\Controller;

/**
* 通知控制器
*/
abstract class NotifyHandler extends Controller
{
    protected $params;

    public function init()
    {
        // 1.验签和参数校检
        $result = $this->checkSignAndOrder();
        if($result) {
            // 2.订单处理
            $this->orderHandle();
            echo "success"; //请不要修改或删除
        } else {
            echo "fail"; //请不要修改或删除
        }
    }

    // 1.验签和校检参数
    public function checkSignAndOrder()
    {
        $this->getOrder();

        if(empty($this->params)) {
            $this->processError('订单不存在');
        }
        $result = \alipay\Notify::check($this->params);

        if(!$result) {
            $this->processError('校检失败');
        }
        return $result;
    }

    // 2.订单处理
    public function orderHandle()
    {$_POST['trade_status'] = 'TRADE_SUCCESS';
        if($_POST['trade_status'] == 'TRADE_SUCCESS') {
            $orderStatus = $this->checkOrderStatus();
            if(!$orderStatus) {
                // 判断订单状态, 如果订单未做过处理, 则处理自己核心业务
                $handlerResult = $this->handle();
                if(!$handlerResult) {
                    // 如果订单未处理成功
                    exit('fail');
                }
            }
        }
    }

    /**
     * 获取订单信息, 用于校检
     * @return array 订单数组, 必须包含订单号和订单金额
     * @param string $params['out_trade_no'] 商户订单
     * @param float  $params['total_amount'] 订单金额
     */
    abstract protected function getOrder();

    /**
     * 检测订单状态, 用于判断订单是否已经做过处理
     * 原因: 本次业务处理较慢, 没来得及echo 'success', 同一订单的通知多次到达, 会造成多次修改数据库, 所以有必要进行订单状态确认
     *
     * @return Boolean true表示已经处理过 false表示未处理过
     */
    abstract protected function checkOrderStatus();

    /**
     * 处理自己业务
     * @return Boolean true表示业务处理 false表示处理失败
     */
    abstract protected function handle();

    /**
     * 统一错误处理接口
     * @param  string $msg 错误描述
     */
    private static function processError($msg)
    {
        throw new \think\Exception($msg);
    }
}