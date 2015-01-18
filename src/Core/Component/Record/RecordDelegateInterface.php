<?php

namespace Kula\Core\Component\Record;

interface RecordDelegateInterface {
	
	function getSelectedRecordBarTemplate();
	
	function getRecordBarTemplate();
	
	function getRecordIDStack();
	
	function getBaseTable();
	
	function getBaseKeyFieldName();
	
}