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
    function changeHistoryField($field, $fieldName, $oldValue, $newValue, $desc = '', $reason_id =''){
        return array(
                     'field' => $field, 'filed_name' => $fieldName, 
                     'old_value' => $oldValue, 'new_value' => $newValue, 'desc' => $desc,
                     'reason_id' => $reason_id,
              );
    }
}

if (!function_exists('convertToFloat')) {
    
    function convertToFloat($num, $dec =2) {
        return round($num, $dec);
    }
}

if (!function_exists('convertToInt')) {
    function convertToInt($num) {
        return (int) $num;
    }
}

if (!function_exists('getAlertBody')) {

    function getAlertBody($old, $new, $diff, $title, $text, $description) {
        return array(
            'title' => $title, 'text' => $text,
            'old_value' => $old, 'difference' => $diff,
            'new_value' => $new, 'desc' => $description
        );
    }
}
?>