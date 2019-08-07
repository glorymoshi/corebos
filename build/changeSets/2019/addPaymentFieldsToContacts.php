<?php
/*************************************************************************************************
 * Copyright 2019 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
* Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
* file except in compliance with the License. You can redistribute it and/or modify it
* under the terms of the License. JPL TSolucio, S.L. reserves all rights not expressly
* granted by the License. coreBOS distributed by JPL TSolucio S.L. is distributed in
* the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
* warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
* applicable law or agreed to in writing, software distributed under the License is
* distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
* either express or implied. See the License for the specific language governing
* permissions and limitations under the License. You may obtain a copy of the License
* at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
*************************************************************************************************/

class addPaymentFieldsToContacts extends cbupdaterWorker {

	public function applyChange() {
		global $adb;
		if ($this->hasError()) {
			$this->sendError();
		}
		if ($this->isApplied()) {
			$this->sendMsg('Changeset '.get_class($this).' already applied!');
		} else {
			$fieldLayout=array(
				'Contacts' => array(
					'LBL_PAYMENT_INFORMATION'=> array(
						'totalamount' => array(
							'label' => 'Total Amount',
							'columntype'=>'decimal(25,6)',
							'typeofdata'=>'N~O',
							'uitype'=>'7',
							'displaytype'=>'2',
						),
						'totalpending' => array(
							'label' => 'Total Pending',
							'columntype'=>'decimal(25,6)',
							'typeofdata'=>'N~O',
							'uitype'=>'7',
							'displaytype'=>'2',
						),
						'balance' => array(
							'label' => 'Balance',
							'columntype'=>'decimal(25,6)',
							'typeofdata'=>'N~O',
							'uitype'=>'7',
							'displaytype'=>'2',
						),
					),
				),
			);
			$this->massCreateFields($fieldLayout);
			$this->sendMsg('Changeset '.get_class($this).' applied!');
			$this->markApplied();
		}
		$this->finishExecution();
	}

	public function undoChange() {
		if ($this->isBlocked()) {
			return true;
		}
		if ($this->hasError()) {
			$this->sendError();
		}
		if ($this->isSystemUpdate()) {
			$this->sendMsg('Changeset '.get_class($this).' is a system update, it cannot be undone!');
		} else {
			if ($this->isApplied()) {
				$fieldLayout=array(
					'Contacts' => array(
						'totalamount',
						'totalpending',
						'balance',
					),
				);
				$this->massDeleteFields($fieldLayout);
				$this->sendMsg('Changeset '.get_class($this).' undone!');
				$this->markUndone();
			} else {
				$this->sendMsg('Changeset '.get_class($this).' not applied!');
			}
		}
		$this->finishExecution();
	}
}