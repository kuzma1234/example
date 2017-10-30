<?php
/**
 * Discount card form.
 * Used for adding discount cards to clients.
 */
class Card_Form_CardByTemplate extends Card_Form_ByTemplate {
    public function init() {
        parent::init();
        $this->addElement('text', 'user_id', [
                'required' => true,
                'validators' => [
                    new IDiscount_Validate_ClientId()
                ]
            ]);
    }
    protected function getUser() {
        $uid = $this->getValue('user_id');
        if (filter_var($uid, FILTER_VALIDATE_EMAIL)) {
            $this->user = $this->em->getRepository('Application_Model_User')->findOneBy(['email' => $uid]);
        } else {
            $this->user = $this->em->getRepository('Application_Model_User')->find($uid);
        }
        return $this->user;
    }
}
