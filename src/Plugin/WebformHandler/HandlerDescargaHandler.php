<?php declare(strict_types = 1);
namespace Drupal\handler_descarga\Plugin\WebformHandler;


use Drupal\Core\Form\FormStateInterface;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;

use GuzzleHttp\Client;


/**
 * Webform Date Validation handler.
 *
 * @WebformHandler(
 *   id = "handler_descarga",
 *   label = @Translation("Handler descarga datos filtrados en un csv."),
 *   category = @Translation("Custom"),
 *   description = @Translation("Handler descarga datos filtrados en un csv."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class HandlerDescargaHandler extends WebformHandlerBase {

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

        parent::validateForm($form, $form_state, $webform_submission);



        if (!$form_state->hasAnyErrors()) {
            $data = $webform_submission->getData();
            //dpm($data,"Datos");
            if ($data['fecha_inicio'] > $data['fecha_fin']) {
               $form_state->setErrorByName('', $this->t('La fecha de inicio no puede ser mayor que la fecha de fin.'));

            } else if ((int)$data['idboya'] < 10) { //En caso de que sea una boya grande
                        $this->limitateDate($form, $form_state, $webform_submission);
            }


        }
    }

    private function limitateDate ($form, $form_state, $webform_submission) {
        $data = $webform_submission->getData();
        $fechaInicial = new \DateTime($data['fecha_inicio']);
        $fechaFinal = new \DateTime($data['fecha_fin']);

        $diferencia = $fechaInicial->diff($fechaFinal);

        $dias = $diferencia->days;

        if ($dias > 31) {
            $form_state->setErrorByName('', $this->t('El rango de fechas no puede ser mayor a 31 días.'));

        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

        $config = \Drupal::config('connection.settings');

        $configData = ['string_con' => $config->get()];

        $baseUrlGrande = $configData["string_con"]["urlBoyaGrande"];
        $baseUrlChica = $configData["string_con"]["urlBoyaChica"];

        if (!$form_state->hasAnyErrors()) {

                $data = $webform_submission->getData();

                if ((int)$data['idboya'] < 10){//Boya grande

                    $url = $baseUrlGrande . $data['idboya'] . '/tracks' . '/' . $data['fecha_inicio'] . (' 00:00:00/')
                    . $data['fecha_fin'] . (' 00:00:00') . ('/csv');
                    $response = new AjaxResponse();
                    $response->addCommand(new RedirectCommand($url));
                    $form_state->setResponse($response);

                } else { //Boya chica
                    $url = $baseUrlChica . $data['idboya'] . '/' . $data['fecha_inicio'] . '/' . $data['fecha_fin'] . ('/csv');
                    $response = new AjaxResponse();
                    $response->addCommand(new RedirectCommand($url));
                    $form_state->setResponse($response);

                }

        }

    }

}
?>