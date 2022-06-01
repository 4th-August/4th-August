<?php
namespace Topxia\Service\Order\OrderProcessor;

use Exception;
use Topxia\Common\NumberToolkit;
use Topxia\Service\Common\ServiceKernel;

class ProductOrderProcessor extends BaseProcessor implements OrderProcessor
{
    protected $router = "product_show";

    public function getRouter()
    {
        return $this->router;
    }

    public function preCheck($targetId, $userId)
    {

        $course = $this->getCourseService()->getCourse($targetId);

        if($course['type'] !== 'product'){
            return array('error' => '参数错误');
        }

        if ($course['status'] != 'published') {
            return array('error' => '不能购买未发布的商品!');
        }

        return array();
    }

    public function getOrderInfo($targetId, $fields)
    {
        $course = $this->getCourseService()->getCourse($targetId);

        if (empty($course)) {
            throw new Exception("找不到要购买的商品!");
        }

        $users                                   = $this->getUserService()->findUsersByIds($course['teacherIds']);
        list($coinEnable, $priceType, $cashRate) = $this->getCoinSetting();

        //重新计算价格
        if ( !is_null($fields['type']) && !is_null(intval($fields['num']))) {
            $course['travelDetail'] = json_decode($course['travelDetail'], true);
            foreach ($course['travelDetail']['carousel'] as $kk) {
                if ($kk['title'] == $fields['type']) {
                    //验证库存
                    if (intval($fields['num']) > 0 && intval($fields['num']) <= intval($kk['number'])) {
                        $totalPrice = $course['coinPrice'] = $kk['price'] * intval($fields['num']);
                    } else {
                        throw new Exception("库存不足!");
                    }
                }
            }

        }else{
            throw new Exception("参数错误!");
        }


//        $totalPrice = 0;

        if (!$coinEnable) {
//            $totalPrice = $course["price"];
            return array(
                'totalPrice' => $totalPrice,
                'targetId'   => $targetId,
                'targetType' => "product",
                'type'       =>$fields['type'],
                'num'        => intval($fields['num']),
                'course'     => empty($course) ? null : $course,
                'users'      => $users
            );
        }

        if ($priceType == "Coin") {
//            $totalPrice = $course["coinPrice"];
        } elseif ($priceType == "RMB") {
//            $totalPrice = $course["price"];
            $maxCoin    = NumberToolkit::roundUp($course['maxRate'] * $totalPrice / 100 * $cashRate);
        }

        list($totalPrice, $coinPayAmount, $account, $hasPayPassword) = $this->calculateCoinAmount($totalPrice, $priceType, $cashRate);

        if (!isset($maxCoin)) {
            $maxCoin = $coinPayAmount;
        }

        return array(
            'course'         => empty($course) ? null : $course,
            'users'          => empty($users) ? null : $users,
            'totalPrice'     => $totalPrice,
            'targetId'       => $targetId,
            'targetType'     => "product",
            'cashRate'       => $cashRate,
            'priceType'      => $priceType,
            'account'        => $account,
            'type'       =>$fields['type'],
            'num'        => intval($fields['num']),
            'hasPayPassword' => $hasPayPassword,
            'coinPayAmount'  => $coinPayAmount,
            'maxCoin'        => $maxCoin
        );
    }

    public function shouldPayAmount($targetId, $priceType, $cashRate, $coinEnabled, $fields)
    {
        $totalPrice = $this->getTotalPrice($targetId, $priceType, $fields);

        $amount = $totalPrice;

        //优惠码优惠价格

        if (isset($fields["couponCode"]) && trim($fields["couponCode"]) != "") {
            $couponResult = $this->afterCouponPay(
                $fields["couponCode"],
                'course',
                $targetId,
                $totalPrice,
                $priceType,
                $cashRate
            );

            if (isset($couponResult["useable"]) && $couponResult["useable"] == "yes" && isset($couponResult["afterAmount"])) {
                $amount = $couponResult["afterAmount"];
            } else {
                unset($couponResult);
            }
        }

        //虚拟币优惠价格

        if (array_key_exists("coinPayAmount", $fields)) {
            $amount = $this->afterCoinPay(
                $coinEnabled,
                $priceType,
                $cashRate,
                $amount,
                $fields['coinPayAmount'],
                $fields["payPassword"]
            );
        }

        if ($priceType == "Coin") {
            $amount = $amount / $cashRate;
        }

        if ($amount < 0) {
            $amount = 0;
        }

        $totalPrice = NumberToolkit::roundUp($totalPrice);
        $amount     = NumberToolkit::roundUp($amount);

        return array(
            $amount,
            $totalPrice,
            empty($couponResult) ? null : $couponResult
        );
    }

    public function createOrder($orderInfo, $fields)
    {
        $orderInfo['productType']=$fields['type'];
        $orderInfo['productNum']=$fields['num'];
        //检查地址
        $address= $this->getUserService()->getUserAddress(intval($fields['address']));
        if($orderInfo['userId'] !== $address['userId']){
            throw new Exception("收货地址错误!");
        }
        unset($address['isD']);
        unset($address['id']);
        unset($address['userId']);
        $orderInfo['productAddress']=json_encode($address);

        return $this->getCourseOrderService()->createOrder($orderInfo);
    }


    protected function getTotalPrice($targetId, $priceType ,$fields = null)
    {
        $totalPrice = 0;
        $course     = $this->getCourseService()->getCourse($targetId);

        //重新计算价格
        if ( !is_null($fields['type']) && !is_null(intval($fields['num']))) {
            $course['travelDetail'] = json_decode($course['travelDetail'], true);
            foreach ($course['travelDetail']['carousel'] as $kk) {
                if ($kk['title'] == $fields['type']) {
                    //验证库存
                    if (intval($fields['num']) > 0 && intval($fields['num']) <= intval($kk['number'])) {
                        $totalPrice = $course['coinPrice'] = $kk['price'] * intval($fields['num']);
                    } else {
                        throw new Exception("库存不足!");
                    }
                }
            }

        }else{
            throw new Exception("参数错误!");
        }

//        if ($priceType == "RMB") {
//            $totalPrice = $course["price"];
//        } elseif ($priceType == "Coin") {
//            $totalPrice = $course["coinPrice"];
//        }

        $totalPrice = (float) $totalPrice;
        return $totalPrice;
    }

    public function doPaySuccess($success, $order)
    {
        if (!$success) {
            return;
        }
        //更新收入，销量
        $course     = $this->getCourseService()->getCourse($order['targetId']);
        $data['income']=$course['income']+$order['amount'];
        $data['studentNum']=$course['studentNum']+$order['productNum'];
//        var_dump($data);exit;
        $this->getCourseService()->updateCourse($course['id'], $data);

//        $this->getCourseOrderService()->doSuccessPayOrder($order['id']);

        return;
    }

    public function getOrderBySn($sn)
    {
        return $this->getOrderService()->getOrderBySn($sn);
    }

    public function updateOrder($id, $fileds)
    {
        return $this->getOrderService()->updateOrder($id, $fileds);
    }

    public function getNote($targetId)
    {
        $course = $this->getCourseService()->getCourse($targetId);
        return str_replace(' ', '', strip_tags($course['about']));
    }

    public function getTitle($targetId)
    {
        $course = $this->getCourseService()->getCourse($targetId);
        return str_replace(' ', '', strip_tags($course['title']));
    }

    public function pay($payData)
    {
        return $this->getPayCenterService()->pay($payData);
    }

    public function callbackUrl($router, $order, $container)
    {
        $goto = !empty($router) ? $container->get('router')->generate($router, array('id' => $order["targetId"]), true) : $this->generateUrl('homepage', array(), true);
        return $goto;
    }

    public function cancelOrder($id, $message, $data)
    {
        return $this->getOrderService()->cancelOrder($id, $message, $data);
    }

    public function createPayRecord($id, $payData)
    {
        return $this->getOrderService()->createPayRecord($id, $payData);
    }

    public function generateOrderToken()
    {
        return 'c'.date('YmdHis', time()).mt_rand(10000, 99999);
    }

    public function getOrderInfoTemplate()
    {
        return "TopxiaWebBundle:Course:orderInfo";
    }

    public function isTargetExist($targetId)
    {
        $course = $this->getCourseService()->getCourse($targetId);

        if (empty($course) || $course['status'] == 'closed') {
            return false;
        }

        return true;
    }

    protected function getCouponService()
    {
        return ServiceKernel::instance()->createService('Coupon.CouponService');
    }

    protected function getAppService()
    {
        return ServiceKernel::instance()->createService('CloudPlatform.AppService');
    }

    protected function getCourseService()
    {
        return ServiceKernel::instance()->createService('Course.CourseService');
    }

    protected function getSettingService()
    {
        return ServiceKernel::instance()->createService('System.SettingService');
    }

    protected function getUserService()
    {
        return ServiceKernel::instance()->createService('User.UserService');
    }

    protected function getCourseOrderService()
    {
        return ServiceKernel::instance()->createService("Course.CourseOrderService");
    }

    protected function getOrderService()
    {
        return ServiceKernel::instance()->createService('Order.OrderService');
    }

    protected function getPayCenterService()
    {
        return ServiceKernel::instance()->createService('PayCenter.PayCenterService');
    }
}
