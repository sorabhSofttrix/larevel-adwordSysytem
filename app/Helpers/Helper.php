<?php
 
if (!function_exists('getResponseObject')) {
    /**
     * Get the reponse for api response in array structure.
     *
     * @param  status, data, responseCode, error
     *
     * @return array
     */
    function getResponseObject($status, $data, $responseCode, $error){
        return array(
            'status' => $status,
            'data' => $data,
            'responseCode' => $responseCode,
            'error' => $error
        );
    }
}

if (!function_exists('changeHistoryField')) {
    /**
     * Get the changeHistoryField for account history in array structure.
     *
     * @param  status, data, responseCode, error
     *
     * @return array
     */
    function changeHistoryField($field, $fieldName, $oldValue, $newValue, $desc = ''){
        return array('field' => $field, 'filed_name' => $fieldName, 'old_value' => $oldValue, 'new_value' => $newValue, 'desc' => $desc);
    }
}

?>