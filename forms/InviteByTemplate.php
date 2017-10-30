<?php
/**
 * User invitational form.
 */
class Card_Form_InviteByTemplate extends Card_Form_ByTemplate {
    private $password;
    /**
     * Discount configuration ranges.
     * @var array
     */
    public function init() {
        parent::init();
        $this->addElement('text', 'email', [
                'required' => true,
                'validators' => [
                    new Zend_Validate_EmailAddress(),
                    new IDiscount_Validate_EmailUnique()
                ],
                'filters' => ['stringTrim']
            ])
            ->addElement('text', 'phone', [
                'required' => true,
            ])
            ->addElement('text', 'name', [
                'required' => true,
            ]);
        $this->getElement('email')->setErrorMessages(['This email address already used or invalid']);
    }
    protected function getUser() {
        $this->user = new Application_Model_User('client');
        $this->password = $this->user->generatePassword();
        $this->user->setName($this->getValue('name'))
            ->setEmail($this->getValue('email'))
            ->setStatus(0)
            ->setPhone($this->getValue('phone'))
            ->setPassword($this->password);
        $this->em->persist($this->user);
        $this->em->flush();
        return $this->user;
    }

    public function update() {
        parent::update();
        $logo = $this->card->getCompany()->getOwner()->getImage();
        IDiscount_Notification_Email::getInstance()
            ->setType(IDiscount_Notification_Email::INVITATIONAL)
            ->bindData([
                'appStoreUrl' => 'https://itunes.apple.com/en/app/idiscountclient/id1018748932?mt=8',
                'googlePlayUrl' => 'https://play.google.com/store/apps/details?id=by.jerminal.android.idiscount.r',
                'companyLogo' => $logo instanceof Application_Model_Image ? $logo->getPath() : null,
                'cover' => $this->template->getCover()->getPath(),
                'name' => $this->user->getName(),
                'brandName' => $this->card->getCompany()->getBrand(),
                'type' => $this->card->getType(),
                'discount' => $this->card->getDiscount(),
                'range' => $this->card->getDiscountRange(),
            ]);
        $this->user->sendPasswordEmail($this->password);
        return $this->card;
    }
}
