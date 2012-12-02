<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Frans van der Veen [netcreators] <extensions@netcreators.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

$currentDir = dirname(__FILE__) . '/';
require_once($currentDir . '../includes.php');

class tx_ncgovpermits_appointment_view extends tx_ncgovpermits_base_view {
	/**
	 * Initializes the class.
	 *
	 * @param object $controller the controller object
	 */
	public function initialize(&$controller) {
		parent::initialize($controller);
		$this->addCssIncludeToHeader(
			$this->controller->configModel->get('includeCssFile'),
			$this->controller->configModel->get('includeCssFilePathIsRelative')
		);
	}

	/**
	 * Shows a selector where a user can select the products he wants to create an appointment for.
	 *
	 * @param string	$step
	 * @return string	the content
	 */
	public function getViewSelectProducts($step, $errors=false) {
		$subparts = array();
		$this->addTranslationLabel('label_next');
		$this->addTranslationLabel('label_error_feedback');
		$this->addTranslationLabel('label_error_no_products_found');
		$main = 'VIEWSELECTPRODUCTS';

		$contentElement = 'content.';
		if($this->controller->getSessionWasExpired() === true) {
			$contentElement = $contentElement . $step . 'Expired';
		} else {
			$contentElement = $contentElement . $step;
		}

		$subparts['CONTENT_MESSAGE'] = $this->getContentObject($contentElement);
		//'content.' . $step

		if($this->controller->productsModel->getCount() == 0) {
			$subparts['CLUSTERS'] = array();
		} else {
			$subparts['NO_PRODUCTS_FOUND'] = array();
			$columns = $this->controller->productsModel->getFieldList();
			if(!tx_nclib::isLoopable($columns)) {
				// critical
				throw new tx_nclib_exception('label_error_undefined', $this->controller);
			}
			$clusters = array();
			while($this->controller->productsModel->hasNextRecord()) {
				$index = $this->controller->productsModel->getCurrentIndex();
				$clusterId = $this->controller->productsModel->getField('cluster');

				// TODO: get wraps
				foreach($columns as $column) {
					$marker = 'PRODUCT_' . strtoupper($column);
					$subparts['CLUSTERS'][$clusterId]['PRODUCTS'][$index][$marker] =
						$this->controller->productsModel->getField($column);
				}
				$link = $this->controller->getLinkToController(
					$this->controller->productsModel->getField('name'),
					tx_nclib_tsfe_model::getPageId(),
					array('productIds' => array($this->controller->productsModel->getId())),
					$step,
					''
				);
				$clusters[$clusterId][] = $this->controller->productsModel->getId();
				$subparts['CLUSTERS'][$clusterId]['CLUSTER'] = $this->controller->productsModel->getField('cluster_name');
				$subparts['CLUSTERS'][$clusterId]['PRODUCTS'][$index]['PRODUCT_LINK'] = $link;
				$subparts['CLUSTERS'][$clusterId]['PRODUCTS'][$index]['PRODUCT_CHECKED'] = '';
				$this->controller->productsModel->moveToNextRecord();
			}
			$output = '';
			$first=true;
			foreach($clusters as $cluster => $products) {
				if($first) {
					$first = false;
				} else {
					$output .= ';';
				}
				$output .= $cluster . '=' . implode(',', $products);
			}
			$subparts['CLUSTER_LIST'] = $output;
		}

		if(tx_nclib::isLoopable($errors)) {
			foreach($errors as $index=>$error) {
				$subparts['HAS_ERRORS'][$index]['ERROR_MESSAGE'] = $this->getTranslatedLabel($error);
			}
		} else {
			$subparts['HAS_ERRORS'] = array();
		}

		$subparts['STEP'] = $step;
		$subparts['MODE'] = '';

		// add disable js
		$this->addJsIncludeToHeader(
			$this->controller->configModel->get('includeJsFile'),
			$this->controller->configModel->get('includeJsFilePathIsRelative')
		);

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows appointment details.
	 *
	 * @param	string	$step	the current step
	 * @return string	the content
	 */
	public function getViewAppointmentDetails($step, $userData, $date, $time, $duration, $persons, $link, $code = false,
		$counterName=false,	$oldDate=false, $oldTime=false, $oldDuration=false, $oldNumberOfPersons=false) {
		$subparts = array();
		$main = 'VIEWAPPOINTMENTDETAILS';
		$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step);
		$maxPersonsAllowed = $this->controller->configModel->get('maxNumberOfPersonsAllowedForProduct');
		$products = array();
		while($this->controller->productsModel->hasNextRecord()) {
			$index = $this->controller->productsModel->getCurrentIndex();
			$products[] = '"' . $this->controller->productsModel->getField('name') . '"';
			$this->controller->productsModel->moveToNextRecord();
		}
		$products = implode(',', $products);
		$labelContinue = false;
		if($link == 'continue') {
			$labelContinue = 'label_continue';
			$labelDetails = 'label_appointment_details';
		} elseif($link == 'confirm') {
			$labelContinue = 'label_confirm_appointment';
			$labelDetails = 'label_confirm_appointment_details';
		} elseif($link == 'confirm_none') {
			$labelDetails = 'label_confirm_appointment_details';
		} else {
			$labelDetails = 'label_appointment_details';
		}
		$this->addTranslationLabel(
			$labelDetails,
			array(
				'products' => $products,
				'date' => $this->getFullDate($date),
				'time' => tx_nclib_datetime::getTime24FromTimestamp($time),
				'duration' => $duration,
				'counter' => $counterName
			)
		);
		$subparts['LABEL_DETAILS'] = $this->getTranslatedLabel($labelDetails);
		if($oldDate != false && $oldTime != false) {
			$this->addTranslationLabel(
				'label_old_appointment_details',
				array(
					'date' => $this->getFullDate($oldDate),
					'time' => tx_nclib_datetime::getTime24FromTimestamp($oldTime),
					'duration' => $oldDuration,
				)
			);
			if($oldNumberOfPersons > 1) {
				if($oldNumberOfPersons > $maxPersonsAllowed) {
					$this->addTranslationLabel('label_more_than', array('maxNumberOfPersons' => $maxPersonsAllowed));
					$oldNumberOfPersons = $this->getTranslatedLabel('label_more_than');
				}
				$this->addTranslationLabel('label_old_multiple_persons', array('persons' => $oldNumberOfPersons));
			} else {
				$subparts['LABEL_OLD_MULTIPLE_PERSONS'] = '';
			}
		} else {
			$subparts['LABEL_OLD_APPOINTMENT_DETAILS'] = '';
			$subparts['LABEL_OLD_MULTIPLE_PERSONS'] = '';
		}
		if($persons > 1) {
			if($persons > $maxPersonsAllowed) {
				$this->addTranslationLabel('label_more_than', array('maxNumberOfPersons' => $maxPersonsAllowed));
				$persons = $this->getTranslatedLabel('label_more_than');
			}
			$this->addTranslationLabel('label_multiple_persons', array('persons' => $persons));
		} else {
			$subparts['LABEL_MULTIPLE_PERSONS'] = '';
		}
		if($code === false) {
			$subparts['LABEL_YOUR_CODE'] = '';
		} else {
			$this->addTranslationLabel('label_your_code', array('code'=>$code));
		}
		if($labelContinue != false) {
			$subparts['LINK_CONFIRM'] = $this->controller->getLinkToController(
				$this->getTranslatedLabel($labelContinue),
				tx_nclib_tsfe_model::getPageId(),
				array(
					'date' => date('Ymd', $date),
					'time' => $time
				),
				$step,
				''
			);
			$subparts['LABEL_CONFIRM'] = $this->getTranslatedLabel($labelContinue);
		} else {
			$subparts['SUBMIT_CONTINUE'] = array();
			/*$subparts['LABEL_CONFIRM'] = $this->getTranslatedLabel('label_confirm_appointment');
			$subparts['LINK_CONFIRM'] = '';*/
		}
		$subparts['MODE'] = '';
		$subparts['STEP'] = $step;

		if($labelContinue != false) {
			$subparts['HAS_REQUIREMENTS'] = array();
		} else {
			$this->addTranslationLabel('label_email_required');
			$subparts['PRODUCT_REQUIREMENTS'] = $this->getProductRequirements(
				$this->controller->productsModel->getRequiredsList()
			);
		}

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows the screen where the user can enter his appointment code, which will be looked up.
	 *
	 * @return string	the content
	 */
	public function getViewEnterAppointmentCode($step, $mode=false) {
		$subparts = array();
		$this->addTranslationLabels(array(
			'label_appointment_code',
			// general
			'label_next',
		));
		$main = 'VIEWENTERAPPOINTMENTCODE';
		$invalid = '';
		if($mode !== false) {
			$mode = str_replace('_', ' ', $mode);
			$mode = ucwords($mode);
			$invalid = str_replace(' ', '', $mode);
		}

		$contentElement = 'content.';
		if($this->controller->getSessionWasExpired() === true) {
			$contentElement = $contentElement  . $step . 'Expired';
		} else {
			$contentElement = $contentElement . $step . $invalid;
		}
		$subparts['CONTENT_MESSAGE'] = $this->getContentObject($contentElement);
		//'content.' . $step . $invalid

		$subparts['STEP'] = $step;
		$subparts['MODE'] = '';
		$subparts['APPOINTMENT_CODE'] = $this->controller->getPiVar('code');

		$link = $this->controller->getLinkToController(
			'door',
			tx_nclib_tsfe_model::getPageId(),
			false,
			$step,
			''
		);
		$subparts['LINK'] = $link;
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows search screen which allows the appointment_group user to find appointments.
	 *
	 * @return string	the content
	 */
	public function getViewFindAppointment($step, $mode, $fields, $numberOfResults) {
		$subparts = array();
		$main = 'VIEWFINDAPPOINTMENT';
		$this->addTranslationLabels(array(
			'label_field_name', 'label_field_code', 'label_field_tx_ncgovpermits_bsn', 'label_field_email',
			'label_field_mobile', 'label_field_phone', 'label_field_date', 'label_field_zip',
			// general
			'label_find_appointment',
		));

		if($numberOfResults > 0) {
			$results = 'MultipleResults';
			$count = 0;
			while($this->controller->appointmentsModel->hasNextRecord()) {
				// eigenlijk hoort dit niet in de view, maarja, moet wat ;)
				if(!$this->controller->ttaddressModel->loadRecordById($this->controller->appointmentsModel->getField('tt_address'))) {
					throw new tx_nclib_exception(
						'label_error_unable_to_load_user_data',
						$this->controller,
						array('uid' => $this->appointmentsModel->getId())
					);
				}
				if(!$this->controller->productsModel->loadProductsByIds($this->controller->appointmentsModel->getField('products'))) {
					throw new tx_nclib_exception(
						'label_error_unable_to_load_products',
						$this->controller,
						array('uid' => $this->appointmentsModel->getId())
					);
				}
				$count++;

				$linkName =	$this->controller->appointmentsModel->getField('start_date')
					. ' ' . $this->controller->appointmentsModel->getField('start_time')
					. ' ' . $this->controller->appointmentsModel->getField('code')
					. ' ' . $this->controller->ttaddressModel->getField('first_name')
					. ' ' . $this->controller->ttaddressModel->getField('middle_name')
					. ' ' . $this->controller->ttaddressModel->getField('last_name')
					. ' ' . $this->controller->ttaddressModel->getField('email')
					. ' ' . $this->controller->ttaddressModel->getField('phone')
					. ' ' . $this->controller->ttaddressModel->getField('mobile');
				if($this->controller->appointmentsModel->getField('was_cancelled')) {
					$linkName .= ' ' . $this->getTranslatedLabel('label_already_cancelled');
				}

				$subparts['APPOINTMENTS'][$count]['APPOINTMENT'] = $this->controller->getLinkToController(
					$linkName,
					tx_nclib_tsfe_model::getPageId(),
					array(
						'code' => $this->controller->appointmentsModel->getField('code'),
						'viewAppointment' => 1
					),
					$step,
					$mode
				);
				$this->controller->appointmentsModel->moveToNextRecord();
			}
		} elseif($numberOfResults == 0) {
			$results = 'NoResults';
			$subparts['APPOINTMENTS_FOUND'] = array();
		} else {
			$subparts['APPOINTMENTS_FOUND'] = array();
		}
		$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step . $results);
		if(tx_nclib::isLoopable($fields)) {
			foreach($fields as $key => $value) {
				$subparts['CI_' . $key] = $value;
			}
		}
		$subparts['STEP'] = $step;
		$subparts['MODE'] = $mode;
		// add css for form layout
		/*$this->addCssIncludeToHeader(
			$this->controller->configModel->get('includeCssFile'),
			$this->controller->configModel->get('includeCssFilePathIsRelative')
		);*/
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows the confirmation screen wether an appointment should be cancelled.
	 *
	 * @return string	the content
	 */
	public function getViewConfirmAppointmentReason($step, $cancel=true) {
		$subparts = array();
		if($cancel) {
			$subparts['LABEL_FIELD_REASON'] = $this->getTranslatedLabel('label_field_cancel_reason');
			$subparts['LABEL_CONFIRM'] = $this->getTranslatedLabel('label_confirm_cancel_appointment');
		} else {
			$subparts['LABEL_FIELD_REASON'] = $this->getTranslatedLabel('label_field_move_reason');
			$subparts['LABEL_CONFIRM'] = $this->getTranslatedLabel('label_confirm_move_appointment');
		}
		$main = 'VIEWCONFIRMAPPOINTMENTREASON';
		$subparts['STEP'] = $step;
		$subparts['MODE'] = '';
		/*$this->addCssIncludeToHeader(
			$this->controller->configModel->get('includeCssFile'),
			$this->controller->configModel->get('includeCssFilePathIsRelative')
		);*/
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows the user registration form, (with feedback errors)
	 *
	 * @return string 	the content
	 */
	public function getViewRegisterUserInformation($step, $userData, $errors=false, $selectMorePersons=false) {
		$this->addTranslationLabels(array(
			// gender
			'label_field_gender', 'label_field_gender_male', 'label_field_gender_female',
			// name
			'label_field_first_name', 'label_field_middle_name', 'label_field_last_name',
			// address
			'label_field_address', 'label_field_zip', 'label_field_city',
			// contact
			'label_field_mobile', 'label_field_phone', 'label_field_email',
			'label_field_retype_email',
			'label_field_send_email', 'label_field_remind_per_sms',
			// other
			'label_field_tx_ncgovpermits_bsn',
			// multiple persons
			'label_field_multiple_persons', 'label_field_select_number_of_persons',
			// general
			'label_next', 'label_field_required', 'label_field_one_required', 'label_error_feedback',
		));
		$maxNumberOfPersons = $this->controller->configModel->get('maxNumberOfPersonsAllowedForProduct');
		$this->addTranslationLabel('label_field_more_persons', array('maxNumberOfPersons' => $maxNumberOfPersons));

		$main = 'VIEWREGISTERUSERINFORMATION';
		$subparts = array();
		$path = 'content.' . $step;
		$subparts['CONTENT_MESSAGE'] = $this->getContentObject($path);

		if(tx_nclib::isLoopable($errors)) {
			$index = 0;
			foreach($errors as $name=>$rules) {
				if(tx_nclib::isLoopable($rules)) {
					foreach($rules as $rule=>$value) {
						if($value === true) {
							$label = $this->getTranslatedLabel('label_field_' . $name . '_' . $rule);
							$subparts['HAS_ERRORS']['ERRORS'][$index]['ERROR_MESSAGE'] = $label;
							$index++;
						}
					}
				}
			}
		} else {
			$subparts['HAS_ERRORS'] = array();
		}
		if(tx_nclib::isLoopable($userData)) {
			foreach($userData as $name=>$value) {
				$subparts['CI_' . strtoupper($name)] = $value;
			}
		}
		switch($userData['tx_ncgovpermits_gender']) {
			case 0:
				$subparts['CI_GENDER_MALE'] = ' checked';
				$subparts['CI_GENDER_FEMALE'] = '';
				break;
			case 1:
				$subparts['CI_GENDER_MALE'] = '';
				$subparts['CI_GENDER_FEMALE'] = ' checked';
				break;
			default:
				$subparts['CI_GENDER_MALE'] = '';
				$subparts['CI_GENDER_FEMALE'] = '';
		}
		if($userData['send_email'] == 1) {
			$subparts['CI_SEND_EMAIL'] = ' checked';
		} else {
			$subparts['CI_SEND_EMAIL'] = '';
		}
		if($userData['remind_per_sms'] == 1) {
			$subparts['CI_REMIND_PER_SMS'] = ' checked';
		} else {
			$subparts['CI_REMIND_PER_SMS'] = '';
		}
		if($selectMorePersons === false) {
			$subparts['CAN_SELECT_MULTIPLE_PERSONS'] = array();
		} else {
			$numberOfPersons = $userData['number_of_persons'];
			if(empty($numberOfPersons) || $numberOfPersons < 1 || $numberOfPersons > ($maxNumberOfPersons+1)) {
				$numberOfPersons = 1;
			}
			for($index=1; $index < $maxNumberOfPersons+1; $index++) {
				$subparts['MULTIPLE_PERSONS'][$index]['BLA'] = '1';
				$subparts['MULTIPLE_PERSONS'][$index]['SELECTED'] = '';
				if($userData['number_of_persons'] == $index) {
					$subparts['MULTIPLE_PERSONS'][$index]['SELECTED'] = ' selected';
				}
			}
			$subparts['MAX_PERSONS_SELECTED'] = '';
			if($numberOfPersons == $maxNumberOfPersons+1) {
				$subparts['MAX_PERSONS_SELECTED'] = ' selected';
			}
			$subparts['MAX_PERSONS'] = $maxNumberOfPersons + 1;
		}

		$subparts['STEP'] = $step;
		$subparts['MODE'] = '';

		// add disable js
		$this->addJsIncludeToHeader(
			$this->controller->configModel->get('includeJsFile'),
			$this->controller->configModel->get('includeJsFilePathIsRelative')
		);
		/*$this->addCssIncludeToHeader(
			$this->controller->configModel->get('includeCssFile'),
			$this->controller->configModel->get('includeCssFilePathIsRelative')
		);*/

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows date selector.
	 *
	 * @return string	the content
	 */
	public function getViewSelectDate($step, $dates) {
		$subparts = array();
		$main = 'VIEWSELECTDATE';
		if(tx_nclib::isLoopable($dates)) {
			// ok, let's select the main intro message
			$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step);
			$index=0;
			foreach($dates as $timestamp => $times) {
				$subparts['DATES'][$index]['DATE'] = $this->controller->getLinkToController(
					$this->getFullDate($timestamp),
					tx_nclib_tsfe_model::getPageId(),
					array('date' => date('Ymd', $timestamp)),
					$step,
					''
				);
				$index++;
			}
		} else {
			// no, let's select the main intro message explaining that there is not an appointment available
			$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step . 'NoTimeAvailable');
			// link to restart the wizard
			$subparts['LINK_BACK'] = $this->controller->getLinkToController(
				$this->getTranslatedLabel('label_restart_wizard'),
				tx_nclib_tsfe_model::getPageId(),
				false,
				'',
				''
			);
			$subparts['DATES'] = array();
		}
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows Time selector.
	 *
	 * @return string	the content
	 */
	public function getViewSelectTime($step, $selectedDate, $dates) {
		$subparts = array();
		$this->addTranslationLabel('label_next');
		$main = 'VIEWSELECTTIME';
		if(tx_nclib::isLoopable($dates)) {
			// ok, let's select the main intro message
			$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step);
			$subparts['DATE_YMD'] = date('Ymd', $selectedDate);

			foreach($dates as $timestamp => $times) {
				if($timestamp == $selectedDate) {
					$subparts['DATE'] = $this->getFullDate($timestamp);
					if(tx_nclib::isLoopable($times)) {
						$index=0;
						foreach($times as $startTime => $time) {
							$subparts['TIMES'][$index]['TIME_START'] = $time[0];
							$subparts['TIMES'][$index]['TIME_END'] = $time[1];
							$subparts['TIMES'][$index]['TIME'] = $time[2];
							$subparts['TIMES'][$index]['LINK_URL'] = $this->controller->getLinkToController(
								false,
								tx_nclib_tsfe_model::getPageId(),
								array(
									'date' => date('Ymd', $timestamp),
									'time' => $time[2]
								),
								$step,
								''
							);
							$index++;
						}
					}
				}
			}
		} else {
			throw new tx_nclib_exception('label_error_times_unavailable', $this->controller);
		}
		$subparts['STEP'] = $step;
		$subparts['MODE'] = '';
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows the changed appointment and the new appointment details.
	 *
	 * @return string	the content
	 */
	public function getViewAppointmentChanged($step) {
		$subparts = array();
		$main = 'VIEWAPPOINTMENTCHANGED';
		$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step);
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows confirmation that the appointment has been cancelled.
	 *
	 * @return string	the content
	 */
	public function getViewAppointmentCancelled($step) {
		$subparts = array();
		$main = 'VIEWAPPOINTMENTCANCELLED';
		$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step);
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows confirmation that the apointment has been created & saved.
	 *
	 * @return string	the content
	 */
	public function getViewAppointmentSaved($step) {
		$subparts = array();
		$main = 'VIEWAPPOINTMENTSAVED';
		$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step);
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows errors when attempting to create an appointment
	 *
	 * @return string	the content
	 */
	public function getViewDateTimeError($step, $mode) {
		$subparts = array();
		$main = 'VIEWDATETIMEERROR';

		// create a usable auto mode
		$contentMode = str_replace('_', ' ', $mode);
		$contentMode = ucwords($contentMode);
		$contentMode = str_replace(' ', '', $contentMode);

		$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step . $contentMode);

		if($mode != 'no_dates_found') {
			$subparts['RETRY_LINK'] = $this->controller->getLinkToController(
				$this->getTranslatedLabel('label_retry_date'),
				tx_nclib_tsfe_model::getPageId(),
				false,
				$step,
				'try_other_date'
			);
		} else {
			$subparts['RETRY_LINK'] = '';
		}

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
	/**
	 * Shows confirmation that the apointment has been created & saved.
	 *
	 * @return string	the content
	 */
	public function getViewSessionExpired($step) {
		$subparts = array();
		$main = 'VIEWSESSIONEXPIRED';
		$subparts['CONTENT_MESSAGE'] = $this->getContentObject('content.' . $step);
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/view/class.tx_ncgovpermits_appointment_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/view/class.tx_ncgovpermits_appointment_view.php']);
}
?>