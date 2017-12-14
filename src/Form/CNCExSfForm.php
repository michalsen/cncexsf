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
    $defaultValue = json_decode($cncexsf);

    $defaultFile = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $defaultValue->cncexsf_wsdl]);;
    foreach ($defaultFile as $key => $image) {
      $fid = $key;
    }

        if (isset($sfCred)) {
            $SFbuilder = new \Phpforce\SoapClient\ClientBuilder(
              $sfCred->cncexsf_wsdl,
              $sfCred->cncexsf_user,
              $sfCred->cncexsf_pass . $sfCred->cncexsf_api
            );
          $client = $SFbuilder->build();

         try {
            $sfFields = [];
            $objects = ['Product2', 'Machine_Photo__c'];

            foreach ($objects as $object) {
              // Product2 Fields
              $fields = $client->describeSObjects(array($object));
              $var = $fields[0]->getFields()->toArray();

                foreach ($var as $key => $value) {
                  $sfFields[$object][] = $value->getName();
                }

            }

          } catch (Exception $e) {
            print $e;
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
      '#required' => TRUE,
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

    \Drupal::state()->set('cncexsf', json_encode($value));
  }

}



function SalesforceCredentials() {

    $cncexsf = \Drupal::state()->get('cncexsf');
    $sfCred = json_decode($cncexsf);
    return $sfCred;

}
