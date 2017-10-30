<?php
class Card_IndexController extends BaseController {
    /**
     * Returns cards list refernced to company owned by current user.
     */
    public function indexAction() {
        $form = new IDiscount_Form_Paginate();
        if (!$form->isValid($this->getData())) {
            return $this->send([
                'status' => 'error',
                'errors' => $form->getErrors()
            ]);
        }
        $company = $this->getCurrentUser(true)->getCompany();
        $cards = $this->em->getRepository('Application_Model_Card')->findAllByCompany($company, $form->getValue('offset'));
        $total = (int)$this->em->getRepository('Application_Model_Card')->countAll(['company' => $company]);
        return $this->send([
            'status' => 'ok', 'cards' => array_map(function($c) {
                return $c->getClientInfo();
            }, $cards), 'offset' => (int)$form->getValue('offset'), 'total' => $total
        ]);
    }
    /**
     * Adds new card to user.
     */
    public function newAction() {
        if (false == $this->getCurrentUser(true)->getCompany()->getSubscription()->hasFreeCards()) {
            return $this->send(['status' => 'error', 'message' => _('You have reached cards limit for your payment plan')]);
        }
        $form = new Card_Form_CardByTemplate();
        $form->setCompany($this->getCurrentUser(true)->getCompany());
        if ($form->isValid($this->getData())) {
            $card = $form->update();
            // Push notification.
            IDiscount_Notification_Push::getInstance()->send(
                sprintf(_("Company %s issued new discount card for you"), $card->getCompany()->getBrand()),
                $card->getUser()
            );
            return $this->send(['status' => 'ok', 'card' => $card->getClientInfo()]);
        } else {
            return $this->send(['status' => 'error', 'errors' => $form->getMessages()]);
        }
    }
    /**
     * Creates new user with card and sends invitation email.
     */
    public function inviteAction() {
        if (false == $this->getCurrentUser(true)->getCompany()->getSubscription()->hasFreeCards()) {
            return $this->send(['status' => 'error', 'message' => _('You have reached cards limit for your payment plan')]);
        }
        $company = $this->getCurrentUser(true)->getCompany();
        $form = new Card_Form_InviteByTemplate($company);
        $form->setCompany($company);
        if ($form->isValid($this->getData())) {
            $card = $form->update();
            return $this->send(['status' => 'ok', 'card' => $card->getClientInfo()]);
        } else {
            return $this->send(['status' => 'error', 'errors' => $form->getMessages()]);
        }
    }
    /**
     * Removes requested card from database.
     */
    public function deleteAction() {
        $card = $this->em->getRepository('Application_Model_Card')->findOneBy([
            'id' => $this->getData()['card_id'],
            'company' => $this->getCurrentUser(true)->getCompany()
        ]);
        if (!$card instanceof Application_Model_Card) throw new Exception('Card not found');
        $this->em->remove($card);
        $this->em->flush();
        return $this->send(['status' => 'ok', 'message' => _('Card removed successfully')]);
    }
}