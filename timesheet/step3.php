<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
$object = new Facture($db);
	if ($user->rights->facture->creer)
	{
		$db->begin();
		$error = 0;
                $dateinvoice = now();
			//$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);
				// Si facture standard
                $object->socid				= $mysoc;
                $object->type				= Facture::TYPE_STANDARD;
                //$object->number				= $_POST['facnumber'];
                $object->date				= $dateinvoice;
                //$object->date_pointoftax	= $date_pointoftax;
                //$object->note_public		= trim($_POST['note_public']);
                //$object->note_private		= trim($_POST['note_private']);
                //$object->ref_client			= $_POST['ref_client'];
                //$object->ref_int			= $_POST['ref_int'];
                //$object->modelpdf			= $_POST['model'];
                $object->fk_project			= $projectSelected;
                //$object->cond_reglement_id	= 1;
                //$object->mode_reglement_id	= 1;
                //$object->fk_account         = GETPOST('fk_account', 'int');
                //$object->amount				= $_POST['amount'];
                //$object->remise_absolue		= $_POST['remise_absolue'];
                //$object->remise_percent		= $_POST['remise_percent'];
                //$object->fk_incoterms 		= GETPOST('incoterm_id', 'int');
                //$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
                //$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
                //$object->multicurrency_tx = GETPOST('originmulticurrency_tx', 'int');

                $object->fetch_thirdparty();
                $id = $object->create($user);
                $resArray=$_POST['userTask'];
                if(is_array($resArray))foreach($resArray as $userTaskService){
                    //$userTaskService[$user][$task]=array('duration', 'VAT','Desc','PriceHT','Service','unit_duration','unit_duration_unit');
                    if(is_array($userTaskService ))foreach($userTaskService as $uId => $taskService){
                        if(is_array($taskService ))foreach($taskService as $tId => $service){
                           $startday = dol_mktime(12, 0, 0, $month, 1, $year);
                           $endday = strtotime('last day of the month',$startday);
                            if($service['Service']>0){
                                $product = new Product($db);
                                $product->fetch($service['Service']);
                                
                                $unit_duration_unit=substr($product->duration, -1);
                                $factor=($unit_duration_unit=='h')?3600:8*3600;//FIXME support week and month 
                                $factor=$factor*intval(substr($product->duration,0, -1));
                                $quantity= $service['duration']/$factor;
                                $result = $object->addline($product->description, $product->price, $quantity, $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, $service['Service'], 0, $startday, $endday, 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', $product->fk_unit);

                            }else{
                                $factor=($service['unit_duration_unit']=='h')?3600:8*3600;//FIXME support week and month 
                                $factor=$factor*intval($service['unit_duration_unit']);
                                $quantity= $service['duration']/$factor;
                                $result = $object->addline($service['Desc'], $service['PriceHT'], $quantity, $service['VAT'], '', '', '', 0, $startday, $endday, 0, 0, '', 'HT', '', 1, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', '');

                            }
                        }else $error++;
                    }else $error++;
                }else $error++;
                
     		// End of object creation, we show it
		if ($id > 0 && ! $error)
		{
			$db->commit();
			header('Location: ' . $object->getNomUrl(0,'',0,1,''));
			exit();
		}
		else
		{
			$db->rollback();
			header('Location: ' . $_SERVER["PHP_SELF"] . '?step=0');
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}