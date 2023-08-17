<?php

namespace App\Imports\SalesAdminPortal;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use App\Models\RealEstate\LotInventory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Inventory implements ToCollection {

    public $statusOne = ['available', 'reserved', 'sold', 'pending_migration', 'not_saleable', 'for_review'];
    public $statusTwo = ['Available', 'Current', 'Reservation', 'Ofs Res', 'Engr Res', 'Cash', 'PD', 'Special', 'Not Saleable'];
    public $xlxs_headers = [
        'phase', 'subdivision_name', 'project', 'subdivision', 'project_acronym', 'block', 'bldg', 'lot', 'unit', 
        'client_number', 'status', 'status2', 'color', 'remarks', 'area', 'type', 'price_per_sqm', 'tsp'
    ];
    
    public $response;
    public $has_error = false;
    public $property_type;
    public $insert_data = [];
    public $reported_entry = [];
    public $null_objects = [];
    public $counter = 0;
    public $invalid_data = [];

    public function collection(Collection $rows)
    {
        $this->response = response( ucfirst($this->property_type)  .' Inventory records are successfully imported.', 200);

        /**
         * Clean null excess column when pressing delete button
         */
        foreach( $rows as $key => $colums ) {
            foreach( $colums as $k => $value ) {
                if( $k > 12 ) {
                    unset($colums[$k]);
                }
            }
        }

        if( $this->property_type === 'lot' ) {
            return $this->lot_inventories($rows);
        } else {
            return $this->condominium_inventories($rows);
        }

        return $this->response;
    }

    public function lot_inventories($rows)
    {
        $select_statement = '';
        foreach( $rows as $key => $colums ) {
            $rowNumber = $key + 1;
            foreach( $colums as $k => $value ) {
                // Check if header contains a valid fields A1:G1
                if( $key === 0 ) {
                    if( !in_array($value, $this->xlxs_headers) ) {
                        $this->response = response( 'Wrong header label (' . $value . ')', 400);
                        $this->has_error = true;
                        break;
                    }
                } else {
                    /* 
                     * First check if not nullable fields has a value
                     * Hadle error in bulk insert checking, to prevent integrity constraint violation
                     * This will happen "null value" in excel file when deleting row by pressing "del" key
                     */
                    if( in_array($rows[0][$k], ['subdivision_name', 'subdivision', 'block', 'lot', 'client_number', 'area', 'type', 'color', 'price_per_sqm']) ) {

                        switch ($rows[0][$k]) {
                            case 'phase':
                                $value = strtoupper(trim($value)); 
                                break;
                            case 'subdivision_name':
                            case 'subdivision':
                                $dataTypeCheck = $this->data_type_check($value, 'varchar');
                                if( $dataTypeCheck ) {
                                    $value = strtoupper(trim($value)); 
                                } else {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Subdivision must not be a numeric value.'];
                                }
                                break;
                            case 'block':
                            case 'lot':
                            case 'area':
                            case 'price_per_sqm':
                                $dataTypeCheck = $this->data_type_check($value, 'numeric');
                                $field = str_replace('_', ' ', ucfirst($rows[0][$k]));
                                if( !$dataTypeCheck ) {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => $field . ' must be numeric value and greater than 0.'];
                                }
                                break;
                            case 'color':
                                $dataTypeCheck = $this->data_type_check($value, 'not_null');
                                if( !$dataTypeCheck ) {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Color is required.'];
                                } else {
                                    $value = trim($value);
                                }
                                break;
                            case 'client_number':
                                $dataTypeCheck = $this->data_type_check($value, 'numeric_nullable');
                                if( !$dataTypeCheck ) {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => $field . ' must be numeric value and greater than 0.'];
                                }
                                break;
                            case 'type':
                                $dataTypeCheck = $this->data_type_check($value, 'not_null');
                                if( $dataTypeCheck ) {
                                    $value = strtoupper(trim($value)); 
                                } else {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Type is required.'];
                                }
                                break;
                            default:
                                break;
                        }

                        if( $value === null ) {
                            $this->null_objects[$key] = $key;
                        }
                    }

                    if($rows[0][$k] == 'status') {

                        $value = strtolower(trim($value));

                        if( !in_array($value, $this->statusOne) ) {
                            if( ($value == 'reservation' || $value == 'current' || $value == 'cash') && !is_null($value) && $value !== '' ) {
                                $value = 'reserved';
                            } else {
                                $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Status value is not listed in allowed statuses. (' . implode(', ', $this->statusOne) . ')'];
                            }
                        }
                    }

                    if($rows[0][$k] == 'status2') {

                        $value = trim($value);

                        if( !in_array($value, $this->statusTwo) && !is_null($value) && $value !== '' ) {
                            $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Status2 value is not listed in allowed statuses. (' . implode(', ', $this->statusTwo) . ')'];
                        }

                    }

                    // Construct a bulk insert data to minimize the background process
                    // Note: Null values are included remove in validation below "Rmoving null values"
                    $this->insert_data[$key][$rows[0][$k]] = $value;
                    
                }
            }
            // Stop the process if header is not match to the template
            if( $this->has_error ) {
                break;
            } else {
                // Add additional hardcoded field value for inserting records
                if( isset($this->insert_data[$key]) ) {
                    $this->insert_data[$key]['created_at'] = Carbon::now();
                    $this->insert_data[$key]['property_type'] = $this->property_type;
                }
            }
        }

        return $this->insert_data();
    }

    public function condominium_inventories($rows)
    {
        $select_statement = '';
        foreach( $rows as $key => $colums ) {
            $rowNumber = $key + 1;
            foreach( $colums as $k => $value ) {
                // Check if header contains a valid fields A1:G1
                if( $key === 0 ) {
                    if( !in_array($value, $this->xlxs_headers) ) {
                        $this->response = response( 'Wrong header label (' . $value . ')', 400);
                        $this->has_error = true;
                        break;
                    }
                } else {
                    /* 
                     * First check if not nullable fields has a value
                     * Hadle error in bulk insert checking, to prevent integrity constraint violation
                     * This will happen "null value" in excel file when deleting row by pressing "del" key
                     */
                    if( in_array($rows[0][$k], ['subdivision', 'block', 'lot']) ) {
                        if( $value === null ) {
                            $this->null_objects[$key] = $key;
                        }
                    }

                    if($rows[0][$k] == 'status') {

                        $value = strtolower(trim($value));

                        if( !in_array($value, $this->statusOne) ) {
                            if( $value == 'reservation' || $value == 'current' || $value == 'cash' ) {
                                $value = 'reserved';
                            } else {
                                $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Status value is not listed in allowed statuses. (' . implode(', ', $this->statusOne) . ')'];
                            }
                        }
                    }

                    if($rows[0][$k] == 'status2') {

                        $value = trim($value);

                        if( !in_array($value, $this->statusTwo) && !is_null($value) && $value !== '' ) {
                            $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Status2 value is not listed in allowed statuses. (' . implode(', ', $this->statusTwo) . ')'];
                        }

                    }

                    if($rows[0][$k] == 'area') {
                        $area = $value;
                    }

                    if($rows[0][$k] == 'tsp') {
                        $rows[0][$k] = 'price_per_sqm';
                        $value = $value / $area;
                    }

                    // 'subdivision_name', 'project', 'subdivision', 'project_acronym',

                    $field_label = str_replace('_', ' ', ucfirst($rows[0][$k]));

                    if($rows[0][$k] == 'project') {
                        $rows[0][$k] = 'subdivision_name';
                    }

                    if($rows[0][$k] == 'project_acronym') {
                        $rows[0][$k] = 'subdivision';
                    }

                    if($rows[0][$k] == 'unit') {
                        $rows[0][$k] = 'lot';
                    }

                    if($rows[0][$k] == 'bldg') {
                        $rows[0][$k] = 'block';
                    }

                    if( in_array($rows[0][$k], ['subdivision_name', 'subdivision', 'block', 'lot', 'client_number', 'area', 'type', 'color', 'price_per_sqm']) ) {

                        switch ($rows[0][$k]) {
                            case 'phase':
                                $value = strtoupper(trim($value)); 
                                break;
                            case 'subdivision_name':
                            case 'subdivision':
                                $dataTypeCheck = $this->data_type_check($value, 'varchar');
                                if( $dataTypeCheck ) {
                                    $value = strtoupper(trim($value)); 
                                } else {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => $field_label .' must not be a numeric value.'];
                                }
                                break;
                            case 'lot':
                            case 'area':
                            case 'price_per_sqm':
                                $dataTypeCheck = $this->data_type_check($value, 'numeric');
                                if( !$dataTypeCheck ) {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => $field_label . ' must be numeric value and greater than 0.'];
                                }
                                break;
                            case 'color':
                                $dataTypeCheck = $this->data_type_check($value, 'not_null');
                                if( !$dataTypeCheck ) {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Color is required.'];
                                } else {
                                    $value = trim($value);
                                }
                                break;
                            case 'client_number':
                                $dataTypeCheck = $this->data_type_check($value, 'numeric_nullable');
                                if( !$dataTypeCheck ) {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Client number must be numeric value and greater than 0.'];
                                }
                                break;
                            case 'block':
                            case 'type':
                                $dataTypeCheck = $this->data_type_check($value, 'not_null');
                                if( $dataTypeCheck ) {
                                    $value = strtoupper(trim($value)); 
                                } else {
                                    $this->invalid_data[] = ['row' => $rowNumber, 'value' => $value, 'message' => 'Type is required.'];
                                }
                                break;
                            default:
                                break;
                        }

                        if( $value === null ) {
                            $this->null_objects[$key] = $key;
                        }
                    }

                    // Construct a bulk insert data to minimize the background process
                    // Note: Null values are included remove in validation below "Rmoving null values"

                    $this->insert_data[$key][$rows[0][$k]] = $value;

                    // Revert field name after saving to insert data for next loop
                    if($rows[0][$k] == 'price_per_sqm') {
                        $rows[0][$k] = 'tsp';
                    }

                    if($rows[0][$k] == 'subdivision_name') {
                        $rows[0][$k] = 'project';
                    }

                    if($rows[0][$k] == 'subdivision') {
                        $rows[0][$k] = 'project_acronym';
                    }

                    if($rows[0][$k] == 'lot') {
                        $rows[0][$k] = 'unit';
                    }

                    if($rows[0][$k] == 'block') {
                        $rows[0][$k] = 'bldg';
                    }
                    
                }
            }
            // Stop the process if header is not match to the template
            if( $this->has_error ) {
                break;
            } else {
                // Add additional hardcoded field value for inserting records
                if( isset($this->insert_data[$key]) ) {
                    $this->insert_data[$key]['created_at'] = Carbon::now();
                    $this->insert_data[$key]['property_type'] = $this->property_type;
                }
            }
        }

        return $this->insert_data();
    }

    public function insert_data()
    {
        // Validate if the file have valid entry and not empty.
        if( !empty($this->insert_data) ) {

            // Rmoving null values
            // foreach( $this->null_objects as $key => $value ) {
            //     if( isset($this->insert_data[$key]) ) {
            //         $this->insert_data[$key]['id'] = $this->counter + 1;
            //         $this->reported_entry['sbl_null'][] = $this->insert_data[$key];
            //         unset($this->insert_data[$key]);
            //     }
            // }

            // Removing un-allowed status
            // foreach( $this->invalid_data as $key => $value ) {
            //     if( isset($this->invalid_data[$key]) ) {
            //         unset($this->insert_data[$key]);
            //     }
            // }

            $has_upload = false;

            if( !empty($this->invalid_data) ) {
                $this->response = response( $this->invalid_data, 200);
            } else {
                // Validate if record is existing to prevent duplication
                foreach( $this->insert_data as $key => $records ) {
                    
                    // Make a SQL query to check if selected fields values are existing in lot inventory table using where() chaining
                    // SQL Output: SELECT * FROM lot_inventories WHERE phase = '{phase_entry}' AND subdivision = {subdivision_entry} ....
                    // Please refer below for other fields
                    $select_statement = LotInventory::limit(1);
                    foreach($records as $field => $value) {
                        if( in_array($field, ['phase', 'subdivision', 'block', 'lot']) ) {
                            $select_statement->where($field, '=', $value);
                        }
                    }
                    
                    // Store the existing data in a container for user report
                    // Then remove the subject in the insert_data
                    if( $select_statement->exists() ) {
                        $records['counter'] = $this->counter + 1;
                        $this->reported_entry['existing_records'][] = $records;
                        unset($this->insert_data[$key]);
                    }
                }

                // Insert Records via bulk insert
                try {

                    // Checking due to transfer of records to existing records
                    // To prevent uploading same file entry
                    if( !empty($this->insert_data) ) {
                        $bulk_insert = LotInventory::insert($this->insert_data);
                        if( $bulk_insert ) {
                            if( !empty($this->reported_entry) ) {
                                // 200 http response but there are existing records need to report to user.
                                // Data preparation response for Create excel file for the list of existing records.
                                $this->response = $this->reported_entry;
                            } else {
                                $has_upload = true;
                                $this->response = response( ucfirst($this->property_type) . ' inventory successfully uploaded.', 200);
                            }
                        } else {
                            // For double checking if try catch not display errors in bulk insert
                            $this->response =  response( ucfirst($this->property_type) . ' inventory bulk insert failed.', 500);
                        }
                    } else {
                        if( isset($this->reported_entry['sbl_null']) ) {
                            $this->response = $this->reported_entry;
                        } else {
                            $this->response =  response( 'All entries are already exists.', 200);
                        }
                    }

                } catch(\Exeption $e) {
                    return $e->getMessage();
                }

                // Update Record
                if(!empty($this->reported_entry['existing_records']))  {

                    $update = false;
                    $existing_records = $this->reported_entry['existing_records'];

                    foreach( $existing_records as $key => $record ) {

                        $inventory_query = LotInventory::limit(1);
                        foreach($record as $field => $value) {
                            if( in_array($field, ['phase', 'subdivision', 'block', 'lot']) ) {
                                $inventory_query->where($field, '=', $value);
                            }
                        }

                        $inventory = $inventory_query->first();

                        if( $inventory ) {
                            if( $inventory->status !== 'reserved' && $inventory->status !== 'sold' && $inventory->status !== 'pending_migration' ) {
                                unset($record['counter']);
                                $update = LotInventory::where('id', $inventory->id)->update($record);
                            }
                        }
                        
                        
                    }

                    if( $update ) {
                        $uploaded_msg = ($has_upload) ? ' and uploaded' : '';
                        $this->response = response( ucfirst($this->property_type) . ' inventory successfully updated' . $uploaded_msg, 200);
                    }


                }

            }
        } else {
            $this->response = response('Nothing to import, wrong format or empty file.', 200);
        }

        return $this->response;
    }

    public function data_type_check($value, $datatype)
    {
        $response = false;
        switch ($datatype) {
            case 'varchar':
                if( is_string($value) && $value !== '' && !is_null($value) ) {
                    $response = true;
                }
                break;
            case 'numeric':
                if( is_numeric($value) && $value > 0 && $value !== '' && !is_null($value) ) {
                    $response = true;
                }
                break;
            case 'not_null':
                if( $value !== '' && !is_null($value) ) {
                    $response = true; 
                }
                break;
            case 'numeric_nullable':
                if( (is_numeric($value) && $value > 0) || $value === '' || is_null($value) ) {
                    $response = true;
                }
                break;
            default:
                # code...
                break;
        }
        return $response;
    }

}
