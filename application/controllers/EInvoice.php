<?php
/**
 * E-Invoice API Controller
 * 
 * REST API endpoints for e-Invoice operations
 * 
 * @package		EInvoice
 * @subpackage	Controllers
 * @author		GeekSwebs
 * @license		MIT
 * @version		1.0.0
 */

class EInvoice extends CI_Controller
{
	protected $service;
	protected $model;
	
	public function __construct()
	{
		parent::__construct();
		
		// Load required libraries and models
		$this->load->library('EInvoice_Service');
		$this->load->model('EInvoice_Model');
		
		// Initialize service
		$this->service = new EInvoice_Service();
		$this->model = new EInvoice_Model();
		
		// Set headers for API responses
		$this->output->set_content_type('application/json');
	}
	
	/**
	 * Create a new invoice
	 * 
	 * POST /einvoice/create
	 */
	public function create()
	{
		if ($this->input->method() !== 'post') {
			$this->respond_error('METHOD_NOT_ALLOWED', 'Only POST method is allowed', 'error');
			return;
		}
		
		try {
			$input = $this->input->raw_input_stream;
			$data = json_decode($input, true);
			
			if (!$data) {
				$this->respond_error('INVALID_JSON', 'Invalid JSON in request body', 'validation');
				return;
			}
			
			// Create invoice using service
			$invoice = $this->service->create_invoice($data);
			
			// Validate invoice
			$validation = $this->service->validate_invoice($invoice);
			
			if (!$validation->is_valid()) {
				$response = $this->service->create_response(
					false,
					'Invoice validation failed',
					array(
						'invoice_id' => $invoice->invoice_id,
						'validation' => $validation->to_array()
					),
					'VALIDATION_FAILED'
				);
				$this->output->set_output($response->to_json());
				return;
			}
			
			// Save invoice to database
			$db_invoice_id = $this->model->save_invoice($invoice);
			$this->model->save_invoice_items($db_invoice_id, $invoice->items);
			$this->model->save_validation($validation);
			
			// Return success response
			$response = $this->service->create_response(
				true,
				'Invoice created successfully',
				array(
					'invoice_id' => $invoice->invoice_id,
					'db_id' => $db_invoice_id,
					'status' => $invoice->document_status,
					'total_amount' => $invoice->total_amount
				),
				'INVOICE_CREATED'
			);
			
			$this->output->set_output($response->to_json());
		}
		catch (Exception $e) {
			$this->respond_error('SERVER_ERROR', $e->getMessage(), 'error');
		}
	}
	
	/**
	 * Get invoice details
	 * 
	 * GET /einvoice/get/:invoice_id
	 */
	public function get($invoice_id = '')
	{
		if ($this->input->method() !== 'get') {
			$this->respond_error('METHOD_NOT_ALLOWED', 'Only GET method is allowed', 'error');
			return;
		}
		
		if (!$invoice_id) {
			$this->respond_error('MISSING_PARAMETER', 'Invoice ID is required', 'validation');
			return;
		}
		
		try {
			$invoice_data = $this->model->get_invoice($invoice_id);
			
			if (!$invoice_data) {
				$this->respond_error('NOT_FOUND', 'Invoice not found', 'error');
				return;
			}
			
			$items = $this->model->get_invoice_items($invoice_data->id);
			$validation = $this->model->get_validations($invoice_id);
			$submission = $this->model->get_submission($invoice_id);
			
			$response = $this->service->create_response(
				true,
				'Invoice retrieved successfully',
				array(
					'invoice' => $invoice_data,
					'items' => $items,
					'validation' => $validation,
					'submission' => $submission
				),
				'INVOICE_FOUND'
			);
			
			$this->output->set_output($response->to_json());
		}
		catch (Exception $e) {
			$this->respond_error('SERVER_ERROR', $e->getMessage(), 'error');
		}
	}
	
	/**
	 * Validate invoice
	 * 
	 * POST /einvoice/validate
	 */
	public function validate()
	{
		if ($this->input->method() !== 'post') {
			$this->respond_error('METHOD_NOT_ALLOWED', 'Only POST method is allowed', 'error');
			return;
		}
		
		try {
			$input = $this->input->raw_input_stream;
			$data = json_decode($input, true);
			
			if (!$data) {
				$this->respond_error('INVALID_JSON', 'Invalid JSON in request body', 'validation');
				return;
			}
			
			$invoice = $this->service->create_invoice($data);
			$validation = $this->service->validate_invoice($invoice);
			
			$response = $this->service->create_response(
				$validation->is_valid(),
				$validation->is_valid() ? 'Invoice is valid' : 'Invoice validation failed',
				$validation->to_array(),
				$validation->validation_status === 'valid' ? 'VALID' : 'INVALID'
			);
			
			$this->output->set_output($response->to_json());
		}
		catch (Exception $e) {
			$this->respond_error('SERVER_ERROR', $e->getMessage(), 'error');
		}
	}
	
	/**
	 * Submit invoice to authority
	 * 
	 * POST /einvoice/submit/:invoice_id
	 */
	public function submit($invoice_id = '')
	{
		if ($this->input->method() !== 'post') {
			$this->respond_error('METHOD_NOT_ALLOWED', 'Only POST method is allowed', 'error');
			return;
		}
		
		if (!$invoice_id) {
			$this->respond_error('MISSING_PARAMETER', 'Invoice ID is required', 'validation');
			return;
		}
		
		try {
			$invoice_data = $this->model->get_invoice($invoice_id);
			
			if (!$invoice_data) {
				$this->respond_error('NOT_FOUND', 'Invoice not found', 'error');
				return;
			}
			
			// Reconstruct invoice object
			$invoice_array = (array)$invoice_data;
			$invoice = $this->service->create_invoice($invoice_array);
			
			// Get validation
			$validations = $this->model->get_validations($invoice_id);
			$validation = !empty($validations) ? $validations[0] : $this->service->validate_invoice($invoice);
			
			// Submit invoice
			$submission = $this->service->submit_invoice($invoice, $validation);
			$submission->submission_id = 'SUB-' . time() . '-' . bin2hex(random_bytes(4));
			
			// Save submission
			$this->model->save_submission($submission);
			$this->model->update_invoice_status($invoice_id, 'submitted');
			
			$response = $this->service->create_response(
				$submission->submission_status === 'submitted',
				$submission->submission_status === 'submitted' ? 'Invoice submitted successfully' : 'Invoice submission failed',
				$submission->to_array(),
				'SUBMISSION_' . strtoupper($submission->submission_status)
			);
			
			$this->output->set_output($response->to_json());
		}
		catch (Exception $e) {
			$this->respond_error('SERVER_ERROR', $e->getMessage(), 'error');
		}
	}
	
	/**
	 * Get invoice list
	 * 
	 * GET /einvoice/list
	 */
	public function list_invoices()
	{
		if ($this->input->method() !== 'get') {
			$this->respond_error('METHOD_NOT_ALLOWED', 'Only GET method is allowed', 'error');
			return;
		}
		
		try {
			$page = $this->input->get('page') ? (int)$this->input->get('page') : 1;
			$per_page = $this->input->get('per_page') ? (int)$this->input->get('per_page') : 20;
			$offset = ($page - 1) * $per_page;
			
			// Build filters
			$filters = array();
			if ($this->input->get('status')) {
				$filters['document_status'] = $this->input->get('status');
			}
			if ($this->input->get('seller_id')) {
				$filters['seller_id'] = $this->input->get('seller_id');
			}
			if ($this->input->get('buyer_id')) {
				$filters['buyer_id'] = $this->input->get('buyer_id');
			}
			if ($this->input->get('from_date')) {
				$filters['date_from'] = $this->input->get('from_date');
			}
			if ($this->input->get('to_date')) {
				$filters['date_to'] = $this->input->get('to_date');
			}
			
			$invoices = $this->model->get_invoices($offset, $per_page, $filters);
			$total = $this->model->count_invoices($filters);
			
			$paginated = $this->service->paginate_response($invoices, $total, $per_page, $page);
			
			$response = $this->service->create_response(
				true,
				'Invoices retrieved successfully',
				$paginated,
				'INVOICES_FOUND'
			);
			
			$this->output->set_output($response->to_json());
		}
		catch (Exception $e) {
			$this->respond_error('SERVER_ERROR', $e->getMessage(), 'error');
		}
	}
	
	/**
	 * Get invoice status
	 * 
	 * GET /einvoice/status/:invoice_id
	 */
	public function status($invoice_id = '')
	{
		if ($this->input->method() !== 'get') {
			$this->respond_error('METHOD_NOT_ALLOWED', 'Only GET method is allowed', 'error');
			return;
		}
		
		if (!$invoice_id) {
			$this->respond_error('MISSING_PARAMETER', 'Invoice ID is required', 'validation');
			return;
		}
		
		try {
			$invoice_data = $this->model->get_invoice($invoice_id);
			
			if (!$invoice_data) {
				$this->respond_error('NOT_FOUND', 'Invoice not found', 'error');
				return;
			}
			
			$status = $this->service->get_invoice_status($invoice_id);
			$status->current_status = $invoice_data->document_status;
			
			$submission = $this->model->get_submission($invoice_id);
			if ($submission) {
				$status->submission_status = $submission->submission_status;
			}
			
			$response = $this->service->create_response(
				true,
				'Invoice status retrieved successfully',
				$status->to_array(),
				'STATUS_FOUND'
			);
			
			$this->output->set_output($response->to_json());
		}
		catch (Exception $e) {
			$this->respond_error('SERVER_ERROR', $e->getMessage(), 'error');
		}
	}
	
	/**
	 * Delete invoice
	 * 
	 * DELETE /einvoice/delete/:invoice_id
	 */
	public function delete($invoice_id = '')
	{
		if ($this->input->method() !== 'delete') {
			$this->respond_error('METHOD_NOT_ALLOWED', 'Only DELETE method is allowed', 'error');
			return;
		}
		
		if (!$invoice_id) {
			$this->respond_error('MISSING_PARAMETER', 'Invoice ID is required', 'validation');
			return;
		}
		
		try {
			$result = $this->model->delete_invoice($invoice_id);
			
			if (!$result) {
				$this->respond_error('NOT_FOUND', 'Invoice not found', 'error');
				return;
			}
			
			$response = $this->service->create_response(
				true,
				'Invoice deleted successfully',
				array('invoice_id' => $invoice_id),
				'INVOICE_DELETED'
			);
			
			$this->output->set_output($response->to_json());
		}
		catch (Exception $e) {
			$this->respond_error('SERVER_ERROR', $e->getMessage(), 'error');
		}
	}
	
	/**
	 * Helper method to respond with error
	 * 
	 * @param string $code
	 * @param string $message
	 * @param string $type
	 */
	protected function respond_error($code, $message, $type = 'server_error')
	{
		$error = $this->service->create_error($code, $message, $type);
		$this->output->set_status_header(400);
		$this->output->set_output(json_encode($error->to_array()));
	}
}

/* End of file EInvoice.php */
/* Location: ./application/controllers/EInvoice.php */
