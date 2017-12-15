<?php
/**
  * @file
  * Contains \Drupal\CNCExSf\Form\CNCExSfForm
  */

namespace Drupal\CNCExSf\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
  *  Provides SNTrack Email form
  */
class CNCExSfForm extends FormBase {

  public function getFormId() {
    return 'SNTrack_email_form';
  }

  /**
    * (@inheritdoc)
    */
  public function buildForm(array $form, FormStateInterface $form_state) {


    $sfCred = SalesforceCredentials();

    $cncexsf = \Drupal::state()->get('cncexsf');
    $sfCred = json_decode($cncexsf);


    if (strlen($sfCred->cncexsf_wsdl) > 0) {
        $defaultFile = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $sfCred->cncexsf_wsdl]);
        foreach ($defaultFile as $key => $image) {
          $fid = $key;
        }
    }




    $form = array(
      '#attributes' => array('enctype' => 'multipart/form-data'),
    );

    $form['cncexsf_user'] = array(
      '#title' => t('Salesforce User'),
      '#type' => 'textfield',
      '#default_value' => $sfCred->cncexsf_user,
      '#required' => TRUE
    );

    $form['cncexsf_pass'] = array(
      '#title' => t('Salesforce Password'),
      '#type' => 'textfield',
      '#default_value' => $sfCred->cncexsf_pass,
      '#required' => TRUE
    );

    $form['cncexsf_api'] = array(
      '#title' => t('Salesforce API'),
      '#type' => 'textfield',
      '#default_value' => $sfCred->cncexsf_api,
      '#required' => TRUE
    );


    $validators = array(
      'file_validate_extensions' => array('wsdl'),
    );

    $form['cncexsf_wsdl'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('WSDL'),
      '#description' => $sfCred->cncexsf_wsdl,
      '#required' => FALSE,
      '#default_value' => array($fid),
      '#upload_location' => 'public://wsdl',
      '#upload_validators' => [
        'file_validate_extensions' => ['wsdl'],
       ]
     ];

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
      );

    // $url = Url::fromUserInput('/admin/cncexsf');
    // $link = \Drupal::l('Test', $url);
    // $form['test'] = array(
    //     '#markup' => $link
    //   );

    return $form;

  }

  /**
    * (@inheritdoc)
    */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $value['cncexsf_user'] = $form_state->getValue('cncexsf_user');
    $value['cncexsf_pass'] = $form_state->getValue('cncexsf_pass');
    $value['cncexsf_api'] = $form_state->getValue('cncexsf_api');

    $fid = $form_state->getValue(['cncexsf_wsdl', 0]);
    if (!empty($fid)) {
      $file = File::load($fid);
      $file->setPermanent();
      $file->save();
      $value['cncexsf_wsdl'] = $file->get('uri')->value;
    }

    $check = testCredentials($value);

    \Drupal::state()->set('cncexsf', json_encode($value));
  }

}

// Upon form submission, test connection
function testCredentials($value) {
  $SFbuilder = new \Phpforce\SoapClient\ClientBuilder(
    $value['cncexsf_wsdl'],
    $value['cncexsf_user'],
    $value['cncexsf_pass'],
    $value['cncexsf_api']
  );
  $client = $SFbuilder->build();

    // Ensure Product2 Object exists
    // Wish I could tighten this up right now.
    $sfFields = [];
    $fields = $client->describeSObjects(array('Product2'));
    $var = $fields[0]->getFields()->toArray();
      foreach ($var as $key => $value) {
        $sfFields[$object][] = $value->getName();
      }
  return $return;
}


function SalesforceCredentials() {

    $cncexsf = \Drupal::state()->get('cncexsf');
    $sfCred = json_decode($cncexsf);
    return $sfCred;

}
