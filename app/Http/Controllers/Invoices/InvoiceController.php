<?php

namespace App\Http\Controllers\Invoices;


use DB;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Utility\UtilityHelper;

class InvoiceController extends Controller
{
    use UtilityHelper;
    /**
     * Display a listing of the resource.
     *data
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $title = "Invoice";
            $invoiceList = $this->searchInvoice(null);
            return view('invoice.show_invoice_list',
                            compact('invoiceList',
                                    'title'));    
        }catch(\Exception $ex){
            return view('errors.404');
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $title = "Invoice";
        $_method = 'POST';
        try{
            $incomeAccountItems = array();
            $student = $studentList = $this->searchStudent($id);
            $revenueAccountGroup = $this->getLastRecord('AccountGroupModel',array('account_group_name'=>'Revenues'));
            foreach ($revenueAccountGroup->accountTitles as $accountTitle) {
                foreach ($accountTitle->items as $item) {
                    $incomeAccountItems[] = $item;
                }
            }
            $lastInsertedInvoice = $this->getControlNo('students_invoice');
            $invNumber = $lastInsertedInvoice->AUTO_INCREMENT;
            $invoice = $this->putInvoice();
            return view('invoice.create_update_invoice',
                            compact('title',
                                    '_method',
                                    'student',
                                    'invNumber',
                                    'incomeAccountItems',
                                    'invoice',
                                    'receiptList'));
        }catch(\Exception $ex){
            return view('errors.404');
        }
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $this->removeKeys($request->all(),true,true);
        $data = $input['data'];
        $toConvertData = explode(' ',$input['payment_due_date']);
        $input['payment_due_date'] = str_replace(',',' ',$toConvertData[1]) . $toConvertData[0] . ' ,' . $toConvertData[2];
        $input['payment_due_date'] = date('Y-m-d',strtotime($input['payment_due_date']));
        unset($input['data']);
        $student = $this->searchStudent($input['student_id']);
        try{
            //Insert Invoice
            $studInvId = $this->insertRecords('students_invoice',$input,false); 
            $this->populateListOfToInsertItems($data,
                                                'Revenues',
                                                'invoice_id',
                                                $studInvId,
                                                'InvoiceModel');

            $dataToInsert = $this->populateListOfToInsertItems($data,
                                                                'Revenues',
                                                                'invoice_id',
                                                                $studInvId,
                                                                'InvoiceModel');
            //Insert Invoice Items
            $this->insertRecords('invoice_items',$dataToInsert,true);

            //Insert Journal Entry for Invoice
            $this->insertRecords('journal_entry',$this->createJournalEntry($dataToInsert,
                                                                            'Invoice',
                                                                            'invoice_id',
                                                                            $studInvId,
                                                                            'Created invoice for Student ' .
                                                                                $student->stud_first_name . ' ' .
                                                                                $student->stud_last_name,
                                                                            $input['total_amount']),
                                true);

            $this->createSystemLogs('Created New Invoice Record');
            flash()->success('Record successfully created');
            echo $studInvId;
        }catch(\Exception $ex){
            echo 'Error ' . $ex->getMessage() . ' ' . $ex->getLine();

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $title = 'Invoice';
        try{
            $invoice = $this->searchInvoice($id);
            if($invoice != NULL){
                return view('invoice.show_invoice',
                            compact('invoice',
                                    'title'));
            }else{
                return view('errors.404');
            }
        }catch(\Exception $ex){
            return view('errors.404');
        }
        
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $title = "Invoice";
        $_method = 'PATCH';
        try{
            $invoice = $this->searchInvoice($id);
            if($invoice != NULL && !($invoice->is_paid)){
                $incomeAccountItems = array();
                $student = $studentList = $this->searchStudent($invoice->student_id);
                $revenueAccountGroup = $this->getLastRecord('AccountGroupModel',array('account_group_name'=>'Revenues'));
                foreach ($revenueAccountGroup->accountTitles as $accountTitle) {
                    foreach ($accountTitle->items as $item) {
                        $incomeAccountItems[] = $item;
                    }
                }
                $invNumber = $id;

                return view('invoice.create_update_invoice',
                                compact('title',
                                        '_method',
                                        'student',
                                        'invNumber',
                                        'incomeAccountItems',
                                        'invoice'));
            }else{
                return view('errors.404');
            }
        }catch(\Exception $ex){
            return view('errors.404');
        }
        
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $this->removeKeys($request->all(),false,true);
        $data = $input['data'];
        $toConvertData = explode(' ',$input['payment_due_date']);
        $input['payment_due_date'] = str_replace(',',' ',$toConvertData[1]) . $toConvertData[0] . ' ,' . $toConvertData[2];
        $input['payment_due_date'] = date('Y-m-d',strtotime($input['payment_due_date']));
        unset($input['data']);
        $student = $this->searchStudent($input['student_id']);
        try{
            $dataToInsert = $this->populateListOfToInsertItems($data,
                                                                'Revenues',
                                                                'invoice_id',
                                                                $id,
                                                                'InvoiceModel');

            //Update Invoice
            $this->updateRecords('students_invoice',array($id),$input);

            //Delete Journal Entry before inserting to avoid duplication
            $this->deleteRecords('journal_entry',array('invoice_id'=>$id));

            //Delete Invoice items before inserting to avoid duplication
            $this->deleteRecords('invoice_items',array('invoice_id'=>$id));

            

            //Insert Invoice Items
            $this->insertRecords('invoice_items',$dataToInsert,true);

            //Insert Journal Entry for Invoice
            $this->insertRecords('journal_entry',$this->createJournalEntry($dataToInsert,
                                                                            'Invoice',
                                                                            'invoice_id',
                                                                            $id,
                                                                            'Created invoice for Student ' .
                                                                                $student->stud_first_name . ' ' .
                                                                                $student->stud_last_name,
                                                                            $input['total_amount']),
                                true);
            $this->createSystemLogs('Updated an Existing Invoice Record');
            flash()->success('Record successfully Updated');
            echo $id;
        }catch(\Exception $ex){
            echo 'Error ' . $ex->getMessage();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}