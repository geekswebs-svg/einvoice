<?php
/**
 * E-Invoice Model
 * 
 * Handles database operations for invoices, validations, and submissions
 * 
 * @package		EInvoice
 * @subpackage	Models
 * @author		GeekSwebs
 * @license		MIT
 * @version		1.0.0
 */

class EInvoice_Model extends CI_Model
{
	private $invoices_table = 'einvoice_invoices';
	private $items_table = 'einvoice_items';
	private $validations_table = 'einvoice_validations';
	private $submissions_table = 'einvoice_submissions';
	private $statuses_table = 'einvoice_statuses';
	
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Create new invoice in database
	 * 
	 * @param EInvoice_Document $invoice
	 * @return int|bool Invoice database ID or false
	 */
	public function save_invoice($invoice)
	{
		$data = array(
			'invoice_id' => $invoice->invoice_id,
			'invoice_number' => $invoice->invoice_number,
			'invoice_date' => $invoice->invoice_date,
			'invoice_type' => $invoice->invoice_type,
			'due_date' => $invoice->due_date,
			'currency' => $invoice->currency,
			'document_status' => $invoice->document_status,
			'seller_id' => $invoice->seller->party_id,
			'buyer_id' => $invoice->buyer->party_id,
			'subtotal' => $invoice->subtotal,
			'tax_total' => $invoice->tax_total,
			'discount_amount' => $invoice->discount_amount,
			'total_amount' => $invoice->total_amount,
			'payment_terms' => $invoice->payment_terms,
			'notes' => $invoice->notes,
			'digital_signature' => $invoice->digital_signature,
			'irn' => $invoice->irn,
			'custom_fields' => json_encode($invoice->custom_fields),
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		);
		
		$this->db->insert($this->invoices_table, $data);
		return $this->db->insert_id();
	}
	
	/**
	 * Save invoice items
	 * 
	 * @param int $invoice_id Database invoice ID
	 * @param array $items
	 * @return bool
	 */
	public function save_invoice_items($invoice_id, $items)
	{
		foreach ($items as $index => $item) {
			$data = array(
				'invoice_id' => $invoice_id,
				'item_id' => $item->item_id,
				'item_code' => $item->item_code,
				'item_name' => $item->item_name,
				'description' => $item->description,
				'quantity' => $item->quantity,
				'unit_of_measure' => $item->unit_of_measure,
				'unit_price' => $item->unit_price,
				'line_amount' => $item->line_amount,
				'tax_rate' => $item->tax_rate,
				'tax_amount' => $item->tax_amount,
				'total_amount' => $item->total_amount,
				'additional_data' => json_encode($item->additional_data),
				'created_at' => date('Y-m-d H:i:s')
			);
			
			$this->db->insert($this->items_table, $data);
		}
		
		return true;
	}
	
	/**
	 * Save validation result
	 * 
	 * @param EInvoice_Validation $validation
	 * @return bool
	 */
	public function save_validation($validation)
	{
		$data = array(
			'validation_id' => $validation->validation_id,
			'invoice_id' => $validation->invoice_id,
			'validation_status' => $validation->validation_status,
			'validation_schema' => $validation->validation_schema,
			'errors' => json_encode($validation->errors),
			'warnings' => json_encode($validation->warnings),
			'critical_errors' => json_encode($validation->critical_errors),
			'created_at' => $validation->validation_timestamp
		);
		
		$this->db->insert($this->validations_table, $data);
		return true;
	}
	
	/**
	 * Save submission details
	 * 
	 * @param EInvoice_Submission $submission
	 * @return bool
	 */
	public function save_submission($submission)
	{
		$data = array(
			'submission_id' => $submission->submission_id,
			'invoice_id' => $submission->invoice_id,
			'submission_status' => $submission->submission_status,
			'submission_channel' => $submission->submission_channel,
			'government_reference' => $submission->government_reference,
			'irn' => $submission->irn,
			'arn' => $submission->arn,
			'qr_code_data' => $submission->qr_code_data,
			'submission_response' => $submission->submission_response,
			'error_details' => json_encode($submission->error_details),
			'retry_count' => $submission->retry_count,
			'next_retry_time' => $submission->next_retry_time,
			'created_at' => $submission->submission_date,
			'updated_at' => date('Y-m-d H:i:s')
		);
		
		$this->db->insert($this->submissions_table, $data);
		return true;
	}
	
	/**
	 * Update invoice status
	 * 
	 * @param string $invoice_id
	 * @param string $new_status
	 * @param string $reason
	 * @return bool
	 */
	public function update_invoice_status($invoice_id, $new_status, $reason = '')
	{
		$data = array(
			'document_status' => $new_status,
			'updated_at' => date('Y-m-d H:i:s')
		);
		
		$this->db->where('invoice_id', $invoice_id);
		return $this->db->update($this->invoices_table, $data);
	}
	
	/**
	 * Get invoice by invoice_id
	 * 
	 * @param string $invoice_id
	 * @return object|false
	 */
	public function get_invoice($invoice_id)
	{
		$query = $this->db->get_where($this->invoices_table, array('invoice_id' => $invoice_id));
		return $query->row();
	}
	
	/**
	 * Get invoice items
	 * 
	 * @param int $invoice_id Database invoice ID
	 * @return array
	 */
	public function get_invoice_items($invoice_id)
	{
		$query = $this->db->get_where($this->items_table, array('invoice_id' => $invoice_id));
		return $query->result();
	}
	
	/**
	 * Get validation records for invoice
	 * 
	 * @param string $invoice_id
	 * @return array
	 */
	public function get_validations($invoice_id)
	{
		$query = $this->db->get_where($this->validations_table, array('invoice_id' => $invoice_id), 1, 0);
		return $query->result();
	}
	
	/**
	 * Get submission record for invoice
	 * 
	 * @param string $invoice_id
	 * @return object|false
	 */
	public function get_submission($invoice_id)
	{
		$query = $this->db->get_where($this->submissions_table, array('invoice_id' => $invoice_id));
		return $query->row();
	}
	
	/**
	 * Get invoices with pagination
	 * 
	 * @param int $offset
	 * @param int $limit
	 * @param array $filters
	 * @return array
	 */
	public function get_invoices($offset = 0, $limit = 20, $filters = array())
	{
		$query = $this->db->from($this->invoices_table);
		
		if (isset($filters['document_status'])) {
			$query->where('document_status', $filters['document_status']);
		}
		
		if (isset($filters['seller_id'])) {
			$query->where('seller_id', $filters['seller_id']);
		}
		
		if (isset($filters['buyer_id'])) {
			$query->where('buyer_id', $filters['buyer_id']);
		}
		
		if (isset($filters['date_from'])) {
			$query->where('invoice_date >=', $filters['date_from']);
		}
		
		if (isset($filters['date_to'])) {
			$query->where('invoice_date <=', $filters['date_to']);
		}
		
		return $query->limit($limit)->offset($offset)->get()->result();
	}
	
	/**
	 * Count total invoices
	 * 
	 * @param array $filters
	 * @return int
	 */
	public function count_invoices($filters = array())
	{
		$query = $this->db->from($this->invoices_table);
		
		if (isset($filters['document_status'])) {
			$query->where('document_status', $filters['document_status']);
		}
		
		if (isset($filters['seller_id'])) {
			$query->where('seller_id', $filters['seller_id']);
		}
		
		if (isset($filters['buyer_id'])) {
			$query->where('buyer_id', $filters['buyer_id']);
		}
		
		return $query->count_all_results();
	}
	
	/**
	 * Delete invoice and related data
	 * 
	 * @param string $invoice_id
	 * @return bool
	 */
	public function delete_invoice($invoice_id)
	{
		$this->db->where('invoice_id', $invoice_id);
		$invoice = $this->db->get($this->invoices_table)->row();
		
		if ($invoice) {
			// Delete items
			$this->db->where('invoice_id', $invoice->id);
			$this->db->delete($this->items_table);
			
			// Delete validations
			$this->db->where('invoice_id', $invoice_id);
			$this->db->delete($this->validations_table);
			
			// Delete submission
			$this->db->where('invoice_id', $invoice_id);
			$this->db->delete($this->submissions_table);
			
			// Delete invoice
			$this->db->where('invoice_id', $invoice_id);
			return $this->db->delete($this->invoices_table);
		}
		
		return false;
	}
}

/* End of file EInvoice_Model.php */
/* Location: ./application/models/EInvoice_Model.php */
