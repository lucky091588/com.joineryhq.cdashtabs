<?php

use CRM_Cdashtabs_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Cdashtabs_Form_Section extends CRM_Core_Form {

  /**
   * System name for Contact Dashboard Tabs OptionGroup.
   * @var string
   */
  private $_gName = 'cdashtabs';

  /**
   * System ID for Contact Dashboard Tabs OptionGroup.
   * @var int
   */
  private $_gid;

  /**
   * System ID for Contact Dashboard Tabs OptionValue being edited.
   * @var int
   */
  private $_id;

  /**
   * Identifier of the native dashboard section ("N" for sections named "native_N")
   * or UFGroup ID for profile sections ("N" for sections named "uf_group_N")
   * @var int
   */
  private $_sectionId;

  /**
   * The type of section; either 'native' or 'uf_group'.
   * @var string
   */
  private $_type;

  /**
   * Pre-process
   */
  public function preProcess() {
    $this->_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      $this->_gName,
      'id',
      'name'
    );
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'browse'
    );

    // OptionValue name will be in a format like 'native_1', 'profile_17', etc.
    $optionValueName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'name');
    $optionValueNameParts = explode('_', $optionValueName);
    $this->_sectionId = array_pop($optionValueNameParts);
    $this->_type = implode('_', $optionValueNameParts);
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_type == 'native') {
      $defaults['label'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'label');
    }
    elseif ($this->_type == 'uf_group') {
      $defaultValues = json_decode(CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'value'));
      foreach ($defaultValues as $key => $value) {
        $defaults[$key] = $value;
      }
      $defaults['value'] = json_encode($defaultValues);
    }

    $defaults['weight'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'weight');

    return $defaults;
  }

  public function buildQuickForm() {
    // Array of read-only properties to display in the form.
    $optionDetails = [];
    if ($this->_type === 'native') {
      $nativeOptionDetails = CRM_Cdashtabs_Settings::getUserDashboardOptionsDetails($this->_sectionId);
      $optionDetails['sectionId'] = $nativeOptionDetails['sectionId'];
      $optionDetails['type'] = ucfirst($this->_type);

      $this->add('text',
        'label',
        ts('Label'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'),
        TRUE
      );

      $this->addRule('label',
        ts('This Label already exists in the database for this option group. Please select a different Label.'),
        'optionExists',
        ['CRM_Core_DAO_OptionValue', $this->_id, $this->_gid, 'label', FALSE]
      );
    }
    elseif ($this->_type === 'uf_group') {
      $optionDetails['sectionId'] = $this->_sectionId;
      $optionDetails['type'] = E::ts('Profile');
      $profileUrl = CRM_Utils_System::url('/civicrm/admin/uf/group/update', "action=update&id={$this->_sectionId}&context=group", TRUE);
      $optionDetails['label'] = CRM_Cdashtabs_Settings::getProfileTitle($this->_sectionId);
      $optionDetails['labelDesc'] = E::ts('To edit the profile title, please <a href="%1">edit this profile Settings</a>.', array(
        '1' => $profileUrl,
      ));

      $this->add('checkbox',
        'is_cdash',
        E::ts('Display on Contact Dashboard?')
      );

      $this->add('checkbox',
        'is_show_pre_post',
        E::ts('Display pre- and post-help on Contact Dashboard?')
      );

      $this->add('hidden',
        'value',
        E::ts('Value'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'),
        TRUE
      );
    }

    $this->add('number',
      'weight',
      E::ts('Order'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'),
      TRUE
    );

    $this->addRule('weight', E::ts('is a numeric field'), 'numeric');

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ),
    ));

    $this->assign('optionDetails', $optionDetails);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $params = $this->exportValues();
    $values = json_decode($params['value']);

    $saveOptionValue = [
      'label' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'label'),
      'value' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'value'),
      'description' => '',
      'weight' => $params['weight'],
      'is_active' => '1',
      'filter' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'filter', 'id'),
      'option_group_id' => $this->_gid,
      'id' => $this->_id,
    ];

    if ($this->_type === 'native') {
      $saveOptionValue['label'] = $params['label'];
    }
    else {
      if ($values->is_cdash !== $params['is_cdash']) {
        $values->is_cdash = $params['is_cdash'];
      }

      if ($values->is_show_pre_post !== $params['is_show_pre_post']) {
        $values->is_show_pre_post = $params['is_show_pre_post'];
      }

      $saveOptionValue['value'] = json_encode($values);
    }

    $optionValue = CRM_Core_OptionValue::addOptionValue($saveOptionValue, $this->_gName, $this->_action, $this->_id);

    CRM_Core_Session::setStatus(ts('The %1 \'%2\' has been saved.', [
      1 => 'Contact Dashboard Tabs: Section',
      2 => $optionValue->label,
    ]), ts('Saved'), 'success');

    $this->ajaxResponse['optionValue'] = $optionValue->toArray();

    parent::postProcess();
  }

}
