<?php
abstract class Card_Form_ByTemplate extends IDiscount_Form_Base {
    protected $company = NULL;
    protected $template = NULL;
    protected $user = NULL;
    /**
     * Card
     * @var Application_Model_Card
     */
    protected $card = NULL;
    protected $active = true;
    protected $em;
    public function __construct($options = null) {
        $this->em = Zend_Registry::get('entity_manager');
        return parent::__construct($options);
    }
    public function setCompany($company) {
        $this->company = $company;
    }
    public function setActive($active) {
        $this->active = $active;
    }
    public function getActive() {
        return $this->active;
    }
    public function init() {
        $this->addElement('text', 'template_id', [
                'required' => true,
                'validators' => [
                    new Zend_Validate_Digits()
                ]
            ])
            ->addElement('text', 'code', [
                'required' => false,
                'validators' => [
                    new IDiscount_Validate_Code()
                ]
            ]);
    }
    public function isValid($data) {
        $valid = parent::isValid($data);
        if($valid) {
            if (array_key_exists('template_id', $data)) {
                $this->template = $this->em->getRepository('Application_Model_CardTemplate')->findOneBy([
                    'company' => $this->company,
                    'id' => $data['template_id']
                ]);
            }
            if (array_key_exists('code', $data)) {
                $card = $this->em->getRepository('Application_Model_Card')->findOneBy([
                    'company' => $this->company,
                    'code' => $data['code']
                ]);
                if ($card instanceof Application_Model_Card) {
                    $valid = FALSE;
                    $this->getElement('code')->addError('Already exists');
                }
            }
            if (!$this->template instanceof Application_Model_CardTemplate) {
                $valid = FALSE;
                $this->getElement('template_id')->addError('Template not found');
            }
        }
        return $valid;
    }
    abstract protected function getUser();
    public function User() {
        if (!$this->user instanceof Application_Model_User) {
            $this->getUser();  
        }
        return $this->user;
    }
    public function update() {
        if ($this->template->getClub() instanceof Application_Model_Club) {
	    $this->card = new Application_Model_Card($this->company);
	    $this->card->setClub($this->template->getClub());
	    $this->card = $this->saveCard($this->card, $this->template, $this->getValue('code'));
	    foreach ($this->template->getChildren()->toArray() as $child) {
		$card = new Application_Model_Card($child->getCompany());
		$card->setParent($this->card);
		$this->saveCard($card, $child, $this->card->getCode());
	    }
        } elseif ($this->template->getParent() instanceof Application_Model_CardTemplate) {
            $template = $this->template->getParent();
            $gcard = new Application_Model_Card($template->getCompany());
	    $gcard->setClub($template->getClub());
	    $gcard = $this->saveCard($gcard, $template, $this->getValue('code'));
	    foreach ($template->getChildren()->toArray() as $child) {
		$card = new Application_Model_Card($child->getCompany());
		$card->setParent($gcard);
		$card = $this->saveCard($card, $child, $gcard->getCode());
                if ($card->getCompany()->getId()==$this->company->getId()) {
                    $this->card = $card;
                }
	    }
        } else {
	    $this->card = new Application_Model_Card($this->company);
            $this->card = $this->saveCard($this->card, $this->template, $this->getValue('code'));
        }
	return $this->card;
    }
    protected function saveCard(Application_Model_Card $card, Application_Model_CardTemplate $template, $code = NULL) {
        $card->setDiscount($template->getDiscount())
            ->setDiscountRange($template->getDiscountRange())
            ->setLimit($template->getLimit())
            ->setCover($template->getCover())
            ->setType($template->getType())
            ->setUser($this->User())
            ->setActive($this->getActive())
            ->setValidSince($template->getValidSince())
            ->setValidTill($template->getValidTill())
            ->setTemplate($template);
        $this->em->persist($card);
        $this->em->flush();
        $card->regenerateCode($this->em, $code);
        return $card;
    } 
}
