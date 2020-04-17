<?php

namespace Drupal\mimemail_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ExampleForm extends FormBase {

  public function getFormId() {
    return 'mimemail_example_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $dir = NULL, $img = NULL) {
    global $user;

    $form['intro'] = [
      '#markup' => $this->t('Use this form to send a HTML message to an e-mail address. No spamming!'),
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => 'test',
      '#required' => TRUE,
    ];

    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#default_value' => $user->mail,
      '#required' => TRUE,
    ];

    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender name'),
    ];

    $form['from_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender e-mail address'),
    ];

    $form['params'] = [
      '#tree' => TRUE,
      'headers' => [
        'Cc' => [
          '#type' => 'textfield',
          '#title' => $this->t('Cc'),
        ],
        'Bcc' => [
          '#type' => 'textfield',
          '#title' => $this->t('Bcc'),
        ],
        'Reply-to' => [
          '#type' => 'textfield',
          '#title' => $this->t('Reply to'),
        ],
        'List-unsubscribe' => [
          '#type' => 'textfield',
          '#title' => $this->t('List-unsubscribe'),
        ],
      ],
      'subject' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
      ],
      'body' => [
        '#type' => 'textarea',
        '#title' => $this->t('HTML message'),
      ],
      'plain' => [
        '#type' => 'hidden',
        '#states' => [
          'value' => [
            ':input[name="body"]' => ['value' => ''],
          ],
        ],
      ],
      'plaintext' => [
        '#type' => 'textarea',
        '#title' => $this->t('Plain text message'),
      ],
      'attachments' => [
        '#name' => 'files[attachment]',
        '#type' => 'file',
        '#title' => $this->t('Choose a file to send as attachment'),
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send message'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = &$form_state['values'];

    if (!valid_email_address($values['to'])) {
      form_set_error('to', $this->t('That e-mail address is not valid.'));
    }

    $file = file_save_upload('attachment');
    if ($file) {
      $file = file_move($file, 'public://');
      $values['params']['attachments'][] = [
        'filepath' => $file->uri,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state['values'];

    $module = 'mimemail_example';
    $key = $values['key'];
    $to = $values['to'];
    $language = language_default();
    $params = $values['params'];

    if (!empty($values['from_mail'])) {
      module_load_include('inc', 'mimemail');
      $from = mimemail_address([
        'name' => $values['from'],
        'mail' => $values['from_mail'],
      ]);
    }
    else {
      $from = $values['from'];
    }

    $send = TRUE;

    $result = drupal_mail($module, $key, $to, $language, $params, $from, $send);
    if ($result['result'] == TRUE) {
      \Drupal::messenger()->addMessage($this->t('Your message has been sent.'));
    }
    else {
      \Drupal::messenger()->addError($this->t('There was a problem sending your message and it was not sent.'));
    }
  }

}
