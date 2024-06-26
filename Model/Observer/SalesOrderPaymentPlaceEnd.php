<?php


namespace TotalPay\Gateway\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Observer Class (called just after the Sales Order has been Places)
 * Class SalesOrderPaymentPlaceEnd
 * @package TotalPay\Gateway\Model\Observer
 */
class SalesOrderPaymentPlaceEnd implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
    /**
     * @var \TotalPay\Gateway\Helper\Data
     */
    protected $_moduleHelper;

    /**
     * SalesOrderPaymentPlaceEnd constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \TotalPay\Gateway\Helper\Data $moduleHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \TotalPay\Gateway\Helper\Data $moduleHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->_moduleHelper = $moduleHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getData('payment');

        // switch ($payment->getMethod()) {
        //     case \TotalPay\Gateway\Model\Method\Checkout::CODE:
        //         $this->updateOrderStatusToNew($payment);
        //         break;
        //     // case \TotalPay\Gateway\Model\Method\Direct::CODE:
        //     //     $this->updateOrderStatus($payment);
        //     //     break;
        //     default:

            if ($payment->getMethod() == \TotalPay\Gateway\Model\Method\Checkout::CODE) {
                $this->updateOrderStatusToNew($payment);
            }
                // Payment method not implemented. Do nothing.
        //}
    }

    /**
     * Update OrderStatus for the new Order
     *
     * Used by the Checkout Payment method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    protected function updateOrderStatusToNew(\Magento\Payment\Model\InfoInterface $payment)
    {
        $order = $payment->getOrder();

        $configHelper = $this->getModuleHelper()->getMethodConfig(
            $payment->getMethod()
        );

        $this->getModuleHelper()->setOrderStatusByState(
            $order,
            $configHelper->getOrderStatusNew()
        );

        $order->save();
    }

    /**
     * Update Order Status
     *
     * Used by the Direct Payment method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    protected function updateOrderStatus(\Magento\Payment\Model\InfoInterface $payment)
    {
        $helper = $this->getModuleHelper();

        $transactionStatus = $this->getModuleHelper()->getPaymentAdditionalInfoValue(
            $payment,
            $helper::ADDITIONAL_INFO_KEY_STATUS
        );

        if (!$transactionStatus) {
            $order = $payment->getOrder();


            switch ($transactionStatus) {
                case $helper::PENDING:
                case $helper::INCOMPLETE:
                    $redirectUrl = $this->getModuleHelper()->getPaymentAdditionalInfoValue(
                        $payment,
                        $helper::ADDITIONAL_INFO_KEY_REDIRECT_URL
                    );

                    if ($redirectUrl) {
                        $this->getModuleHelper()->setOrderState(
                            $order,
                            $helper::PENDING
                        );
                    }
                    break;
                case $helper::SUCCESSFUL:
                    $this->getModuleHelper()->setOrderStatusByState(
                        $order,
                        \Magento\Sales\Model\Order::STATE_PROCESSING
                    );

                    break;
                default:
                    // Other status. Do nothing.
            }
        }
    }

    /**
     * Get an Instance of the Module Helper Object
     * @return \TotalPay\Gateway\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }
}
