<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Plesklists_Form_PleskSettings extends CRM_Core_Form {
  function buildQuickForm() {

    // add form elements
    $this->add(
      'text', // field type
      'plesk_host', // field name
      ts('Plesk host') // field label
    );
    $this->add(
      'text', // field type
      'plesk_login', // field name
      ts('Plesk user') // field label
    );
    $this->add(
      'password', // field type
      'plesk_password', // field name
      ts('Plesk password') // field label
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  function postProcess() {
    parent::postProcess();
    $vals = $this->controller->exportValues($this->_name);

    // Store form values in CiviCRM settings.
    CRM_Core_BAO_Setting::setItem($vals['plesk_host'], 'plesklists', 'plesklist_host');
    CRM_Core_BAO_Setting::setItem($vals['plesk_login'], 'plesklists', 'plesklist_login');
    CRM_Core_BAO_Setting::setItem($vals['plesk_password'], 'plesklists', 'plesklist_password');
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  function setDefaultValues() {
    $defaults['plesk_host'] = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');
    $defaults['plesk_login'] = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_login');
    $defaults['plesk_password'] = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_password');
    $this->setDefaults($defaults);
  }
}
