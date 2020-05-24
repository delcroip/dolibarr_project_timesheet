<?php
/* Copyright (C) 2010        Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012        Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2016   Philippe Grand  <philippe.grand@atoo-net.com>
 * Copyright (C) 2014   Marcos García   <marcosgdf@gmail.com>
 *
 * This program is free software;you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation;either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */
/**
 *                \file       htdocs/core/modules/supplier_invoice/modules_facturefournisseur.php
 *      \ingroup    facture fournisseur
 *      \brief      File that contains parent class for supplier invoices models
 *                                        and parent class for supplier invoices numbering models
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';        // required for use by classes that inherit
/**
 *        Parent class for supplier invoices models
 */
abstract class ModelPDFTimesheetReport extends CommonDocGenerator
{
    public $error = '';
    //phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    //phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return list of active generation models
     *
     *  @param        DoliDB        $db                        Database handler
     *  @param  integer        $maxfilenamelength  Max length of value to show
     *  @return        array                                                List of numbers
     */
    public static function liste_modeles($db, $maxfilenamelength = 0)
    {
        global $conf;
        $type = 'timesheetReport';
        $liste = array();
        include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        $liste = getListOfModels($db, $type, $maxfilenamelength);
        return $liste;
    }
}
