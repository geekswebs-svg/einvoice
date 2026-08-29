<?php
/**
 * E-Invoice Service Library
 * 
 * Main service for managing e-Invoice operations including generation,
 * validation, submission, and tracking
 * 
 * @package		EInvoice
 * @subpackage	Libraries
 * @author		GeekSwebs
 * @license		MIT
 * @version		1.0.0
 */

class EInvoice_Service
{
	protected $CI;
	protected $config = array();
	protected $api_response;
	
	public function __construct($config = array())
	{
		$this->CI =& get_instance();
		$this->config = $config;
		$this->load_libraries();
	}
	
	/**
	 * Load required libraries and models
	 */
	protected function load_libraries()
	{
		$this->CI->load->library('EInvoice_API');
	}
	
	/**
	 * Create a new invoice document
	 * 
	 * @param array $invoice_data Invoice data array
	 * @return EInvoice_Document
	 */
	public function create_invoice($invoice_data = array())
	{
		$invoice = new EInvoice_Document($invoice_data);
		
		// Set default status
		if (!$invoice->document_status) {
			$invoice->document_status = 'draft';
		}
		
		// Generate invoice ID if not provided
		if (!$invoice->invoice_id) {
			$invoice->invoice_id = $this->generate_invoice_id();
		}
		
		return $invoice;
	}
	
	/**
	 * Validate invoice document
	 * 
	 * @param EInvoice_Document $invoice
	 * @return EInvoice_Validation
	 */
	public function validate_invoice($invoice)
	{
		$validation = new EInvoice_Validation();
		$validation->invoice_id = $invoice->invoice_id;
		$validation->validation_schema = 'CI3_EINVOICE_v1.0';
		
		// Validate basic fields
		if (!$invoice->invoice_number) {
			$validation->add_critical_error('MISSING_INVOICE_NUMBER', 'Invoice number is required', 'invoice_number');
		}
		
		if (!$invoice->invoice_date) {
			$validation->add_critical_error('MISSING_INVOICE_DATE', 'Invoice date is required', 'invoice_date');
		}
		
		if (!$invoice->invoice_date || !$this->is_valid_date($invoice->invoice_date)) {
			$validation->add_critical_error('INVALID_INVOICE_DATE', 'Invoice date format is invalid', 'invoice_date');
		}
		
		if (!$invoice->seller || !$invoice->seller->party_id) {
			$validation->add_critical_error('MISSING_SELLER', 'Seller information is required', 'seller');
		} else {
			$this->validate_party($invoice->seller, $validation, 'seller');
		}
		
		if (!$invoice->buyer || !$invoice->buyer->party_id) {
			$validation->add_critical_error('MISSING_BUYER', 'Buyer information is required', 'buyer');
		} else {
			$this->validate_party($invoice->buyer, $validation, 'buyer');
		}
		
		if (empty($invoice->items)) {
			$validation->add_critical_error('NO_ITEMS', 'Invoice must contain at least one item', 'items');
		} else {
			$this->validate_items($invoice->items, $validation);
		}
		
		if (!$invoice->currency) {
			$validation->add_warning('MISSING_CURRENCY', 'Currency not specified, defaulting to INR', 'currency');
			$invoice->currency = 'INR';
		}
		
		if ($invoice->total_amount <= 0) {
			$validation->add_critical_error('INVALID_AMOUNT', 'Total amount must be greater than zero', 'total_amount');
		}
		
		// Set validation status
		$validation->validation_status = $validation->is_valid() ? 'valid' : 'invalid';
		
		return $validation;
	}
	
	/**
	 * Validate party (buyer/seller) information
	 * 
	 * @param EInvoice_Party $party
	 * @param EInvoice_Validation $validation
	 * @param string $party_type
	 */
	protected function validate_party($party, &$validation, $party_type)
	{
		if (!$party->party_name) {
			$validation->add_critical_error('MISSING_' . strtoupper($party_type) . '_NAME', ucfirst($party_type) . ' name is required', $party_type . '.party_name');
		}
		
		if (!$party->email && !$party->phone) {
			$validation->add_warning('MISSING_' . strtoupper($party_type) . '_CONTACT', ucfirst($party_type) . ' contact information is incomplete', $party_type . '.contact');
		}
		
		if ($party->email && !$this->is_valid_email($party->email)) {
			$validation->add_warning('INVALID_' . strtoupper($party_type) . '_EMAIL', 'Email format is invalid', $party_type . '.email');
		}
	}
	
	/**
	 * Validate invoice items
	 * 
	 * @param array $items
	 * @param EInvoice_Validation $validation
	 */
	protected function validate_items($items, &$validation)
	{
		foreach ($items as $index => $item) {
			if (!$item->item_name) {
				$validation->add_critical_error('MISSING_ITEM_NAME', 'Item name is required', 'items[' . $index . '].item_name');
			}
			
			if ($item->quantity <= 0) {
				$validation->add_critical_error('INVALID_ITEM_QUANTITY', 'Item quantity must be greater than zero', 'items[' . $index . '].quantity');
			}
			
			if ($item->unit_price < 0) {
				$validation->add_critical_error('INVALID_UNIT_PRICE', 'Unit price cannot be negative', 'items[' . $index . '].unit_price');
			}
			
			if ($item->tax_rate < 0 || $item->tax_rate > 100) {
				$validation->add_critical_error('INVALID_TAX_RATE', 'Tax rate must be between 0 and 100', 'items[' . $index . '].tax_rate');
			}
		}
	}
	
	/**
	 * Submit invoice to authority
	 * 
	 * @param EInvoice_Document $invoice
	 * @param EInvoice_Validation $validation
	 * @return EInvoice_Submission
	 */
	public function submit_invoice($invoice, $validation = null)
	{
		// Validate if not already validated
		if (!$validation) {
			$validation = $this->validate_invoice($invoice);
		}
		
		$submission = new EInvoice_Submission();
		$submission->invoice_id = $invoice->invoice_id;
		$submission->submission_channel = 'api';
		
		// Check if invoice is valid before submission
		if (!$validation->is_valid()) {
			$submission->submission_status = 'rejected';
			foreach ($validation->critical_errors as $error) {
				$submission->add_error($error['code'], $error['message']);
			}
			return $submission;
		}
		
		// Simulate submission to government API
		// In production, this would call the actual e-Invoice portal API
		$submission->submission_status = 'submitted';
		$submission->government_reference = $this->generate_reference_number();
		$submission->irn = $this->generate_irn($invoice);
		$submission->qr_code_data = $this->generate_qr_data($submission->irn);
		
		return $submission;
	}
	
	/**
	 * Get invoice status
	 * 
	 * @param string $invoice_id
	 * @return EInvoice_Status
	 */
	public function get_invoice_status($invoice_id)
	{
		$status = new EInvoice_Status();
		$status->invoice_id = $invoice_id;
		$status->current_status = 'draft';
		$status->payment_status = 'unpaid';
		
		return $status;
	}
	
	/**
	 * Generate paginated response
	 * 
	 * @param array $data
	 * @param int $total
	 * @param int $per_page
	 * @param int $current_page
	 * @return array
	 */
	public function paginate_response($data, $total, $per_page = 20, $current_page = 1)
	{
		$pagination = new EInvoice_Pagination($total, $per_page, $current_page);
		
		return array(
			'data' => $data,
			'pagination' => $pagination->to_array()
		);
	}
	
	/**
	 * Generate API response
	 * 
	 * @param bool $success
	 * @param string $message
	 * @param mixed $data
	 * @param string $code
	 * @return EInvoice_API_Response
	 */
	public function create_response($success = true, $message = '', $data = null, $code = '')
	{
		$response = new EInvoice_API_Response();
		$response->success = $success;
		$response->message = $message;
		$response->data = $data;
		$response->code = $code;
		
		return $response;
	}
	
	/**
	 * Generate API error response
	 * 
	 * @param string $code
	 * @param string $message
	 * @param string $type
	 * @return EInvoice_API_Error
	 */
	public function create_error($code, $message, $type = 'server_error')
	{
		return new EInvoice_API_Error($code, $message, $type);
	}
	
	/**
	 * Helper function to generate invoice ID
	 * 
	 * @return string
	 */
	public function generate_invoice_id()
	{
		return 'INV-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
	}
	
	/**
	 * Helper function to generate reference number
	 * 
	 * @return string
	 */
	public function generate_reference_number()
	{
		return 'REF-' . date('YmdHis') . '-' . strtoupper(substr(md5(mt_rand()), 0, 8));
	}
	
	/**
	 * Helper function to generate IRN (Invoice Reference Number)
	 * 
	 * @param EInvoice_Document $invoice
	 * @return string
	 */
	public function generate_irn($invoice)
	{
		$irn_string = $invoice->invoice_number . 
					  $invoice->invoice_date . 
					  $invoice->seller->party_id . 
					  $invoice->buyer->party_id . 
					  round($invoice->total_amount, 2);
		
		return strtoupper(substr(hash('sha256', $irn_string), 0, 64));
	}
	
	/**
	 * Helper function to generate QR code data
	 * 
	 * @param string $irn
	 * @return string
	 */
	public function generate_qr_data($irn)
	{
		return base64_encode(json_encode(array(
			'irn' => $irn,
			'generated_at' => date('Y-m-d H:i:s'),
			'version' => '1.0'
		)));
	}
	
	/**
	 * Validate email format
	 * 
	 * @param string $email
	 * @return bool
	 */
	protected function is_valid_email($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}
	
	/**
	 * Validate date format
	 * 
	 * @param string $date
	 * @param string $format
	 * @return bool
	 */
	protected function is_valid_date($date, $format = 'Y-m-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) === $date;
	}
}

/* End of file EInvoice_Service.php */
/* Location: ./application/libraries/EInvoice_Service.php */
