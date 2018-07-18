<?php

namespace Drupal\mimemail\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\mailsystem\MailsystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mail System settings form.
 */
class AdminForm extends ConfigFormBase {

  /**
   * @var MailsystemManager
   */
  protected $mailManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    parent::__construct($config_factory);
    $this->mailManager = $mail_manager;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('module_handler'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mimemail_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mimemail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mimemail.settings');

    $form = [];
    $form['mimemail']['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Sender name'),
      '#default_value' => $config->get('name') ? $config->get('name') : \Drupal::config('system.site')->get('name'),
      '#size'          => 60,
      '#maxlength'     => 128,
      '#description'   => $this->t('The name that all site emails will be from when using default engine.'),
    ];
    $form['mimemail']['mail'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Sender e-mail address'),
      '#default_value' => $config->get('mail') ? $config->get('mail') : \Drupal::config('system.site')->get('mail'),
      '#size'          => 60,
      '#maxlength'     => 128,
      '#description'   => $this->t('The email address that all site e-mails will be from when using default engine.'),
    ];


    // Get a list of all formats.
    $formats = filter_formats();
    $format_options = [];
    foreach ($formats as $format) {
      $format_options[$format->get('format')] = $format->get('name');
    }
    $form['mimemail']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('E-mail format'),
      '#default_value' => $this->config('mimemail.settings')->get('format'),
      '#options' => $format_options,
      '#access' => count($formats) > 1,
      '#attributes' => ['class' => ['filter-list']],
    ];
    // Check for the existence of a mail.css file in the default theme folder.
    /*$theme = variable_get('theme_default', NULL);
    $mailstyle = drupal_get_path('theme', $theme) . '/mail.css';
    // Disable site style sheets including option if found.
    if (is_file($mailstyle)) {
      variable_set('mimemail_sitestyle', 0);
      $disable_sitestyle = TRUE;
    }
    else {
      $disable_sitestyle = FALSE;
    }

    $form = array();
    $form['mimemail']['mimemail_name'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Sender name'),
      '#default_value' => variable_get('mimemail_name', variable_get('site_name', 'Drupal')),
      '#size'          => 60,
      '#maxlength'     => 128,
      '#description'   => t('The name that all site emails will be from when using default engine.'),
    );
    $form['mimemail']['mimemail_mail'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Sender e-mail address'),
      '#default_value' => variable_get('mimemail_mail', variable_get('site_mail', ini_get('sendmail_from'))),
      '#size'          => 60,
      '#maxlength'     => 128,
      '#description'   => t('The email address that all site e-mails will be from when using default engine.'),
    );
    $form['mimemail']['mimemail_simple_address'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Use simple address format'),
      '#default_value' => variable_get('mimemail_simple_address', FALSE),
      '#description' => t('Use the simple format of user@example.com for all recipient email addresses.'),
    );
    $form['mimemail']['mimemail_sitestyle'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Include site style sheets'),
      '#default_value' => variable_get('mimemail_sitestyle', TRUE),
      '#description'   => t('Gather all style sheets when no mail.css found in the default theme directory.'),
      '#disabled'      => $disable_sitestyle,
    );
    $form['mimemail']['mimemail_textonly'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send plain text email only'),
      '#default_value' => variable_get('mimemail_textonly', FALSE),
      '#description' => t('This option disables the use of email messages with graphics and styles. All messages will be converted to plain text.'),
    );
    $form['mimemail']['mimemail_linkonly'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Link images only'),
      '#default_value' => variable_get('mimemail_linkonly', 0),
      '#description'   => t('This option disables the embedding of images. All image will be available as external content. This can make email messages much smaller.'),
    );
    if (module_exists('mimemail_compress')) {
      $form['mimemail']['mimemail_preserve_class'] = array(
        '#type' => 'checkbox',
        '#title' => t('Preserve class attributes'),
        '#default_value' => variable_get('mimemail_preserve_class', 0),
        '#description' => t('This option disables the removing of class attributes from the message source. Useful for debugging the style of the message.'),
      );
    }

    // Get a list of all formats.
    $formats = filter_formats();
    foreach ($formats as $format) {
      $format_options[$format->format] = $format->name;
    }
    $form['mimemail']['mimemail_format'] = array(
      '#type' => 'select',
      '#title' => t('E-mail format'),
      '#options' => $format_options,
      '#default_value' => variable_get('mimemail_format', filter_fallback_format()),
      '#access' => count($formats) > 1,
      '#attributes' => array('class' => array('filter-list')),
    );

    $form['mimemail']['advanced'] = array(
      '#type' => 'fieldset',
      '#title' => t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['mimemail']['advanced']['mimemail_incoming'] = array(
      '#type' => 'checkbox',
      '#title' => t('Process incoming messages posted to this site'),
      '#default_value' => variable_get('mimemail_incoming', FALSE),
      '#description' => t('This is an advanced setting that should not be enabled unless you know what you are doing.'),
    );
    $form['mimemail']['advanced']['mimemail_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Message validation string'),
      '#default_value' => variable_get('mimemail_key', drupal_random_key()),
      '#required' => TRUE,
      '#description' => t('This string will be used to validate incoming messages. It can be anything, but must be used on both sides of the transfer.'),
    );

    // Get the available mail engines.
    $engines = mimemail_get_engines();
    foreach ($engines as $module => $engine) {
      $engine_options[$module] = $engine['name'] . ': ' . $engine['description'];
    }
    // Hide the settings if only 1 engine is available.
    if (count($engines) == 1) {
      reset($engines);
      variable_set('mimemail_engine', key($engines));
      $form['mimemail']['mimemail_engine'] = array(
        '#type' => 'hidden',
        '#value' => variable_get('mimemail_engine', 'mimemail'),
      );
    }
    else {
      $form['mimemail']['mimemail_engine'] = array(
        '#type' => 'select',
        '#title' => t('E-mail engine'),
        '#default_value' => variable_get('mimemail_engine', 'mimemail'),
        '#options' => $engine_options,
        '#description' => t('Choose an engine for sending mails from your site.'),
      );
    }

    if (variable_get('mimemail_engine', 'mail')) {
      $settings = module_invoke(variable_get('mimemail_engine', 'mimemail'), 'mailengine', 'settings');
      if ($settings) {
        $form['mimemail']['engine_settings'] = array(
          '#type' => 'fieldset',
          '#title' => t('Engine specific settings'),
        );
        foreach ($settings as $name => $value) {
          $form['mimemail']['engine_settings'][$name] = $value;
        }
      }
    }
    else {
      drupal_set_message(t('Please choose a mail engine.'), 'error');
    }*/



    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mimemail.settings');

    // Set the default mimemail format.
    if ($form_state->hasValue('format')) {
      $config->set('format', $form_state->getValue('format'));
    }

    // Set the default mimemail site name.
    if ($form_state->hasValue('name')) {
      $config->set('name', $form_state->getValue('name'));
    }

    // Set the default mimemail site email.
    if ($form_state->hasValue('mail')) {
      $config->set('mail', $form_state->getValue('mail'));
    }

    // Finally save the configuration.
    $config->save();
  }

}
