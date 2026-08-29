<?php
/**
 * E-Invoice API Type Definition Library
 * 
 * Comprehensive API types for e-Invoice integration in CodeIgniter 3
 * Supports invoice generation, validation, submission, and tracking
 * 
 * @package		EInvoice
 * @author		GeekSwebs
 * @license		MIT
 * @version		1.0.0
 */

/**
 * Base API Response Type
 */
class EInvoice_API_Response
{
	public $success = false;
	public $code = null;
	public $message = '';
	public $data = null;
	public $errors = array();
	public $timestamp;
	
	public function __construct()
	{
		$this->timestamp = date('Y-m-d H:i:s');
	}
	
	public function to_array()
	{
		return array(
			'success' => $this->success,
			'code' => $this->code,
			'message' => $this->message,
			'data' => $this->data,
			'errors' => $this->errors,
			'timestamp' => $this->timestamp
		);
	}
	
	public function to_json()
	{
		return json_encode($this->to_array());
	}
}

/**
 * Invoice Item Type
 */
class EInvoice_Item
{
	public $item_id;
	public $item_code;
	public $item_name;
	public $description;
	public $quantity;
	public $unit_of_measure;
	public $unit_price;
	public $line_amount;
	public $tax_rate;
	public $tax_amount;
	public $total_amount;
	public $additional_data = array();
	
	public function __construct($data = array())
	{
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
		$this->calculate_totals();
	}
	
	public function calculate_totals()
	{
		$this->line_amount = $this->quantity * $this->unit_price;
		$this->tax_amount = $this->line_amount * ($this->tax_rate / 100);
		$this->total_amount = $this->line_amount + $this->tax_amount;
	}
	
	public function to_array()
	{
		return array(
			'item_id' => $this->item_id,
			'item_code' => $this->item_code,
			'item_name' => $this->item_name,
			'description' => $this->description,
			'quantity' => $this->quantity,
			'unit_of_measure' => $this->unit_of_measure,
			'unit_price' => $this->unit_price,
			'line_amount' => $this->line_amount,
			'tax_rate' => $this->tax_rate,
			'tax_amount' => $this->tax_amount,
			'total_amount' => $this->total_amount,
			'additional_data' => $this->additional_data
		);
	}
}

/**
 * Invoice Party Type (Buyer/Seller)
 */
class EInvoice_Party
{
	public $party_id;
	public $party_name;
	public $legal_name;
	public $email;
	public $phone;
	public $tax_number;
	public $registration_number;
	public $party_type; // 'seller', 'buyer', 'intermediary'
	public $address;
	public $country;
	public $state;
	public $city;
	public $postal_code;
	public $bank_details = array();
	
	public function __construct($data = array())
	{
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}
	
	public function to_array()
	{
		return array(
			'party_id' => $this->party_id,
			'party_name' => $this->party_name,
			'legal_name' => $this->legal_name,
			'email' => $this->email,
			'phone' => $this->phone,
			'tax_number' => $this->tax_number,
			'registration_number' => $this->registration_number,
			'party_type' => $this->party_type,
			'address' => $this->address,
			'country' => $this->country,
			'state' => $this->state,
			'city' => $this->city,
			'postal_code' => $this->postal_code,
			'bank_details' => $this->bank_details
		);
	}
}

/**
 * Invoice Document Type
 */
class EInvoice_Document
{
	public $invoice_id;
	public $invoice_number;
	public $invoice_date;
	public $invoice_type; // 'invoice', 'credit_note', 'debit_note'
	public $due_date;
	public $currency;
	public $document_status; // 'draft', 'issued', 'submitted', 'accepted', 'rejected'
	
	public $seller;
	public $buyer;
	public $items = array();
	
	public $subtotal = 0;
	public $tax_total = 0;
	public $discount_amount = 0;
	public $total_amount = 0;
	
	public $payment_terms;
	public $notes;
	public $attachments = array();
	public $references = array();
	public $digital_signature;
	public $irn; // IRN for Indian e-Invoice
	public $qr_code;
	public $custom_fields = array();
	
	public function __construct($data = array())
	{
		foreach ($data as $key => $value) {
			if ($key === 'seller' && is_array($value)) {
				$this->seller = new EInvoice_Party($value);
			} elseif ($key === 'buyer' && is_array($value)) {
				$this->buyer = new EInvoice_Party($value);
			} elseif ($key === 'items' && is_array($value)) {
				foreach ($value as $item) {
					$this->add_item($item);
				}
			} elseif (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}
	
	public function add_item($item)
	{
		if (is_array($item)) {
			$item = new EInvoice_Item($item);
		}
		$this->items[] = $item;
		$this->calculate_totals();
	}
	
	public function calculate_totals()
	{
		$this->subtotal = 0;
		$this->tax_total = 0;
		
		foreach ($this->items as $item) {
			$this->subtotal += $item->line_amount;
			$this->tax_total += $item->tax_amount;
		}
		
		$this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_total;
	}
	
	public function to_array()
	{
		$items = array();
		foreach ($this->items as $item) {
			$items[] = $item->to_array();
		}
		
		return array(
			'invoice_id' => $this->invoice_id,
			'invoice_number' => $this->invoice_number,
			'invoice_date' => $this->invoice_date,
			'invoice_type' => $this->invoice_type,
			'due_date' => $this->due_date,
			'currency' => $this->currency,
			'document_status' => $this->document_status,
			'seller' => $this->seller ? $this->seller->to_array() : null,
			'buyer' => $this->buyer ? $this->buyer->to_array() : null,
			'items' => $items,
			'subtotal' => $this->subtotal,
			'tax_total' => $this->tax_total,
			'discount_amount' => $this->discount_amount,
			'total_amount' => $this->total_amount,
			'payment_terms' => $this->payment_terms,
			'notes' => $this->notes,
			'attachments' => $this->attachments,
			'references' => $this->references,
			'digital_signature' => $this->digital_signature,
			'irn' => $this->irn,
			'qr_code' => $this->qr_code,
			'custom_fields' => $this->custom_fields
		);
	}
}

/**
 * Invoice Validation Type
 */
class EInvoice_Validation
{
	public $validation_id;
	public $invoice_id;
	public $validation_status; // 'valid', 'invalid', 'warning'
	public $validation_timestamp;
	public $validation_schema;
	public $errors = array();
	public $warnings = array();
	public $critical_errors = array();
	
	public function __construct($data = array())
	{
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
		$this->validation_timestamp = date('Y-m-d H:i:s');
	}
	
	public function add_error($code, $message, $field = null)
	{
		$this->errors[] = array(
			'code' => $code,
			'message' => $message,
			'field' => $field,
			'timestamp' => date('Y-m-d H:i:s')
		);
	}
	
	public function add_warning($code, $message, $field = null)
	{
		$this->warnings[] = array(
			'code' => $code,
			'message' => $message,
			'field' => $field,
			'timestamp' => date('Y-m-d H:i:s')
		);
	}
	
	public function add_critical_error($code, $message, $field = null)
	{
		$this->critical_errors[] = array(
			'code' => $code,
			'message' => $message,
			'field' => $field,
			'timestamp' => date('Y-m-d H:i:s')
		);
	}
	
	public function is_valid()
	{
		return count($this->critical_errors) === 0;
	}
	
	public function to_array()
	{
		return array(
			'validation_id' => $this->validation_id,
			'invoice_id' => $this->invoice_id,
			'validation_status' => $this->validation_status,
			'validation_timestamp' => $this->validation_timestamp,
			'validation_schema' => $this->validation_schema,
			'errors' => $this->errors,
			'warnings' => $this->warnings,
			'critical_errors' => $this->critical_errors,
			'is_valid' => $this->is_valid()
		);
	}
}

/**
 * Invoice Submission Type
 */
class EInvoice_Submission
{
	public $submission_id;
	public $invoice_id;
	public $submission_date;
	public $submission_status; // 'pending', 'submitted', 'acknowledged', 'rejected', 'failed'
	public $submission_channel; // 'api', 'web_portal', 'email', 'batch'
	public $government_reference;
	public $irn;
	public $arn; // Acknowledgement Reference Number
	public $qr_code_data;
	public $submission_response;
	public $error_details = array();
	public $retry_count = 0;
	public $max_retries = 3;
	public $next_retry_time;
	
	public function __construct($data = array())
	{
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
		$this->submission_date = date('Y-m-d H:i:s');
	}
	
	public function add_error($code, $message, $details = array())
	{
		$this->error_details[] = array(
			'code' => $code,
			'message' => $message,
			'details' => $details,
			'timestamp' => date('Y-m-d H:i:s')
		);
	}
	
	public function can_retry()
	{
		return $this->retry_count < $this->max_retries;
	}
	
	public function increment_retry()
	{
		$this->retry_count++;
		$this->next_retry_time = date('Y-m-d H:i:s', strtotime('+' . pow(2, $this->retry_count) . ' minutes'));
	}
	
	public function to_array()
	{
		return array(
			'submission_id' => $this->submission_id,
			'invoice_id' => $this->invoice_id,
			'submission_date' => $this->submission_date,
			'submission_status' => $this->submission_status,
			'submission_channel' => $this->submission_channel,
			'government_reference' => $this->government_reference,
			'irn' => $this->irn,
			'arn' => $this->arn,
			'qr_code_data' => $this->qr_code_data,
			'submission_response' => $this->submission_response,
			'error_details' => $this->error_details,
			'retry_count' => $this->retry_count,
			'max_retries' => $this->max_retries,
			'next_retry_time' => $this->next_retry_time,
			'can_retry' => $this->can_retry()
		);
	}
}

/**
 * Invoice Status Type
 */
class EInvoice_Status
{
	public $invoice_id;
	public $current_status; // 'draft', 'issued', 'submitted', 'accepted', 'rejected', 'cancelled', 'archived'
	public $status_history = array();
	public $last_status_update;
	public $submission_status;
	public $validation_status;
	public $compliance_status;
	public $payment_status; // 'unpaid', 'partially_paid', 'paid', 'overdue'
	
	public function __construct($data = array())
	{
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
		$this->last_status_update = date('Y-m-d H:i:s');
	}
	
	public function update_status($new_status, $reason = '', $metadata = array())
	{
		$this->status_history[] = array(
			'status' => $this->current_status,
			'new_status' => $new_status,
			'timestamp' => date('Y-m-d H:i:s'),
			'reason' => $reason,
			'metadata' => $metadata
		);
		$this->current_status = $new_status;
		$this->last_status_update = date('Y-m-d H:i:s');
	}
	
	public function to_array()
	{
		return array(
			'invoice_id' => $this->invoice_id,
			'current_status' => $this->current_status,
			'status_history' => $this->status_history,
			'last_status_update' => $this->last_status_update,
			'submission_status' => $this->submission_status,
			'validation_status' => $this->validation_status,
			'compliance_status' => $this->compliance_status,
			'payment_status' => $this->payment_status
		);
	}
}

/**
 * Pagination Type for API Responses
 */
class EInvoice_Pagination
{
	public $total_records;
	public $total_pages;
	public $current_page;
	public $per_page;
	public $has_next;
	public $has_previous;
	
	public function __construct($total = 0, $per_page = 20, $current_page = 1)
	{
		$this->total_records = $total;
		$this->per_page = $per_page;
		$this->current_page = $current_page;
		$this->total_pages = ceil($total / $per_page);
		$this->has_next = $current_page < $this->total_pages;
		$this->has_previous = $current_page > 1;
	}
	
	public function to_array()
	{
		return array(
			'total_records' => $this->total_records,
			'total_pages' => $this->total_pages,
			'current_page' => $this->current_page,
			'per_page' => $this->per_page,
			'has_next' => $this->has_next,
			'has_previous' => $this->has_previous
		);
	}
}

/**
 * API Error Type
 */
class EInvoice_API_Error
{
	public $error_code;
	public $error_message;
	public $error_type; // 'validation', 'authentication', 'authorization', 'not_found', 'server_error', 'rate_limit'
	public $details;
	public $timestamp;
	public $request_id;
	public $documentation_url;
	
	public function __construct($code = '', $message = '', $type = 'server_error')
	{
		$this->error_code = $code;
		$this->error_message = $message;
		$this->error_type = $type;
		$this->timestamp = date('Y-m-d H:i:s');
		$this->request_id = uniqid('REQ_', true);
	}
	
	public function to_array()
	{
		return array(
			'error_code' => $this->error_code,
			'error_message' => $this->error_message,
			'error_type' => $this->error_type,
			'details' => $this->details,
			'timestamp' => $this->timestamp,
			'request_id' => $this->request_id,
			'documentation_url' => $this->documentation_url
		);
	}
}

/* End of file EInvoice_API.php */
/* Location: ./application/libraries/EInvoice_API.php */
