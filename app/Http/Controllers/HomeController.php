<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Model\Invoice;
use App\Model\InvoiceItem;
use App\Model\User;
use App\Model\AdminDetail;
use App\Admin;
use Mail;
use Illuminate\Support\Facades\Auth;
use Session;
use Image\Image\ImageInterface;
use Imagine\Image\Box;
use App\Model\AdminPaymentMap;
use App\Model\PaymentKeys;
use App\Model\PaymentTypes;
use Stripe;
use Lava;

class HomeController extends Controller
{
  public function index(){
    if (Auth::check()) {
      $admin_id = Session::get('admin_id');
      $payment_ac_details = AdminPaymentMap::where('admin_id', '=', $admin_id)->with('PaymentKeys')->first();
      //dd($payment_ac_details->payment_type);
      $admin = Admin::find($admin_id);
      if ($admin) {
        $admin_type = $admin->admin_type;
        $member = AdminDetail::where('admin_id', $admin_id)->first();
        $membership_status = $member->membership;
        return view('home.index',array('title'=>'Invoice System || Create Invoice'), compact('payment_ac_details', 'admin_type', 'membership_status'));

      }
      else
      {
        echo "Fatal error! please contact system adminstrator";
      }
      
    }
    else
    {
      return redirect()->route('admin-login');
    }      
  }
  public function addItems(Request $request) {
      $i = $request->i;
      return view('home.newitem',array('title'=>'Invoice System || Create Invoice'), compact('i'));
  }
  public function Invoice(Request $request){
    //work
      $admin_id = Session::get('admin_id');
      $admin_details = Admin::where('id', $admin_id)->with('admin_details')->first();
      //dd($admin_details);
      if ($admin_details->admin_type == 0 && $admin_details->admin_details->membership == 1) {
        // count how many invoices made 
        $invoice_count = Invoice::where('admin_id', $admin_id)->get()->count();
        $invoice_count = $invoice_count+1;
        if ($invoice_count <= 35) {
          $name = $request->name; //user name
          $user_id = time().substr($name,0,5); //user id
          $email = $request->email; //user email
          $memo = $request->memo; //user memo
          $user = new User;
          $user->user_id = $user_id;
          $user->name = $name;
          $user->email =$email;
          $user->memo = $memo;
          $user->save(); //stored in user table
          $invoice_id = date('ymd').rand('000','999');
          $user_id_invoice = $user->id; //last inserted id
          $tax_rate = $request->tax_rate;
          $count = $request->counter; //counter for loop
          $price_ex_tax = '';
          $items = '';
          for ($i=0; $i <= $count; $i++) { 
            if ($price_ex_tax == '') {
              $price_ex_tax = $request->Quantity[$i] * $request->Price[$i];
            }
            else
            {
              $price_ex_tax = $price_ex_tax+ ($request->Quantity[$i] * $request->Price[$i]);
            }
            $InvoiceItem = new InvoiceItem;
            $InvoiceItem->invoice_id = $invoice_id;
            $InvoiceItem->name = $request->Item[$i];
            $InvoiceItem->qty =$request->Quantity[$i];
            $InvoiceItem->price = $request->Price[$i];
            if(isset($request->tax[$i])) {
              $InvoiceItem->tax_status = 1;
              $InvoiceItem->tax_rate = $tax_rate;
              $InvoiceItem->price_in_tax = ($request->Quantity[$i] * $request->Price[$i]+($request->Quantity[$i] * $request->Price[$i] * ($tax_rate/100)));
            }
            else
            {
              $InvoiceItem->tax_status = 0;
              $InvoiceItem->tax_rate = 0.00;
              $InvoiceItem->price_in_tax = ($request->Quantity[$i] * $request->Price[$i]);
            }
            $InvoiceItem->save(); //stored in user table
            
          }
          $invoice = new Invoice;
          $admin_id = Session::get('admin_id');
          $invoice->user_id = $user_id_invoice;
          $invoice->invoice_id = $invoice_id;
          $invoice->tax_rate = $tax_rate;
          $invoice->total = $price_ex_tax;
          $invoice->memo = $memo;
          $invoice->admin_id = $admin_id;
          $invoice->save();
            $user_name = $name;
            $user_email = $email;
            $admin_users_email="hello@tier5.us";
            $activateLink = url('/').'/client/invoice/'.base64_encode($invoice_id);
            $sent = Mail::send('email.invoice_link', array('name'=>$user_name,'email'=>$user_email,'activate_link'=>$activateLink), 
            function($message) use ($admin_users_email, $user_email,$user_name)
            {
            $message->from($admin_users_email);
            $message->to($user_email, $user_name)->subject('Invoice From INVOICINGYOU.COM');
            });
          return redirect('/invoice-created/'.base64_encode($invoice_id));
              
        }
        else
        {
          return redirect()->route('index')->with('upgrade',"You can't create more invoices upgrade now to create more");
        }
      }
      
      else if ($admin_details->admin_type == 0 && $admin_details->admin_details->membership == 2) {
        $name = $request->name; //user name
          $user_id = time().substr($name,0,5); //user id
          $email = $request->email; //user email
          $memo = $request->memo; //user memo
          $user = new User;
          $user->user_id = $user_id;
          $user->name = $name;
          $user->email =$email;
          $user->memo = $memo;
          $user->save(); //stored in user table
          $invoice_id = date('ymd').rand('000','999');
          $user_id_invoice = $user->id; //last inserted id
          $tax_rate = $request->tax_rate;
          $count = $request->counter; //counter for loop
          $price_ex_tax = '';
          $items = '';
          for ($i=0; $i <= $count; $i++) { 
            if ($price_ex_tax == '') {
              $price_ex_tax = $request->Quantity[$i] * $request->Price[$i];
            }
            else
            {
              $price_ex_tax = $price_ex_tax+ ($request->Quantity[$i] * $request->Price[$i]);
            }
            $InvoiceItem = new InvoiceItem;
            $InvoiceItem->invoice_id = $invoice_id;
            $InvoiceItem->name = $request->Item[$i];
            $InvoiceItem->qty =$request->Quantity[$i];
            $InvoiceItem->price = $request->Price[$i];
            if(isset($request->tax[$i])) {
              $InvoiceItem->tax_status = 1;
              $InvoiceItem->tax_rate = $tax_rate;
              $InvoiceItem->price_in_tax = ($request->Quantity[$i] * $request->Price[$i]+($request->Quantity[$i] * $request->Price[$i] * ($tax_rate/100)));
            }
            else
            {
              $InvoiceItem->tax_status = 0;
              $InvoiceItem->tax_rate = 0.00;
              $InvoiceItem->price_in_tax = ($request->Quantity[$i] * $request->Price[$i]);
            }
            $InvoiceItem->save(); //stored in user table
            
          }
          $invoice = new Invoice;
          $admin_id = Session::get('admin_id');
          $invoice->user_id = $user_id_invoice;
          $invoice->invoice_id = $invoice_id;
          $invoice->tax_rate = $tax_rate;
          $invoice->total = $price_ex_tax;
          $invoice->memo = $memo;
          $invoice->admin_id = $admin_id;
          $invoice->save();
            $user_name = $name;
            $user_email = $email;
            $admin_users_email="hello@tier5.us";
            $activateLink = url('/').'/client/invoice/'.base64_encode($invoice_id);
            $sent = Mail::send('email.invoice_link', array('name'=>$user_name,'email'=>$user_email,'activate_link'=>$activateLink), 
            function($message) use ($admin_users_email, $user_email,$user_name)
            {
            $message->from($admin_users_email);
            $message->to($user_email, $user_name)->subject('Invoice From INVOICINGYOU.COM');
            });
          return redirect('/invoice-created/'.base64_encode($invoice_id));
      }
      else if ($admin_details->admin_type == 0 && $admin_details->admin_details->membership == 3) {
        $name = $request->name; //user name
          $user_id = time().substr($name,0,5); //user id
          $email = $request->email; //user email
          $memo = $request->memo; //user memo
          $user = new User;
          $user->user_id = $user_id;
          $user->name = $name;
          $user->email =$email;
          $user->memo = $memo;
          $user->save(); //stored in user table
          $invoice_id = date('ymd').rand('000','999');
          $user_id_invoice = $user->id; //last inserted id
          $tax_rate = $request->tax_rate;
          $count = $request->counter; //counter for loop
          $price_ex_tax = '';
          $items = '';
          for ($i=0; $i <= $count; $i++) { 
            if ($price_ex_tax == '') {
              $price_ex_tax = $request->Quantity[$i] * $request->Price[$i];
            }
            else
            {
              $price_ex_tax = $price_ex_tax+ ($request->Quantity[$i] * $request->Price[$i]);
            }
            $InvoiceItem = new InvoiceItem;
            $InvoiceItem->invoice_id = $invoice_id;
            $InvoiceItem->name = $request->Item[$i];
            $InvoiceItem->qty =$request->Quantity[$i];
            $InvoiceItem->price = $request->Price[$i];
            if(isset($request->tax[$i])) {
              $InvoiceItem->tax_status = 1;
              $InvoiceItem->tax_rate = $tax_rate;
              $InvoiceItem->price_in_tax = ($request->Quantity[$i] * $request->Price[$i]+($request->Quantity[$i] * $request->Price[$i] * ($tax_rate/100)));
            }
            else
            {
              $InvoiceItem->tax_status = 0;
              $InvoiceItem->tax_rate = 0.00;
              $InvoiceItem->price_in_tax = ($request->Quantity[$i] * $request->Price[$i]);
            }
            $InvoiceItem->save(); //stored in user table
            
          }
          $invoice = new Invoice;
          $admin_id = Session::get('admin_id');
          $invoice->user_id = $user_id_invoice;
          $invoice->invoice_id = $invoice_id;
          $invoice->tax_rate = $tax_rate;
          $invoice->total = $price_ex_tax;
          $invoice->memo = $memo;
          $invoice->admin_id = $admin_id;
          $invoice->save();
            $user_name = $name;
            $user_email = $email;
            $admin_users_email="hello@tier5.us";
            $activateLink = url('/').'/client/invoice/'.base64_encode($invoice_id);
            $sent = Mail::send('email.invoice_link', array('name'=>$user_name,'email'=>$user_email,'activate_link'=>$activateLink), 
            function($message) use ($admin_users_email, $user_email,$user_name)
            {
            $message->from($admin_users_email);
            $message->to($user_email, $user_name)->subject('Invoice From INVOICINGYOU.COM');
            });
          return redirect('/invoice-created/'.base64_encode($invoice_id));
      }

      else {
        $name = $request->name; //user name
          $user_id = time().substr($name,0,5); //user id
          $email = $request->email; //user email
          $memo = $request->memo; //user memo
          $user = new User;
          $user->user_id = $user_id;
          $user->name = $name;
          $user->email =$email;
          $user->memo = $memo;
          $user->save(); //stored in user table
          $invoice_id = date('ymd').rand('000','999');
          $user_id_invoice = $user->id; //last inserted id
          $tax_rate = $request->tax_rate;
          $count = $request->counter; //counter for loop
          $price_ex_tax = '';
          $items = '';
          for ($i=0; $i <= $count; $i++) { 
            if ($price_ex_tax == '') {
              $price_ex_tax = $request->Quantity[$i] * $request->Price[$i];
            }
            else
            {
              $price_ex_tax = $price_ex_tax+ ($request->Quantity[$i] * $request->Price[$i]);
            }
            $InvoiceItem = new InvoiceItem;
            $InvoiceItem->invoice_id = $invoice_id;
            $InvoiceItem->name = $request->Item[$i];
            $InvoiceItem->qty =$request->Quantity[$i];
            $InvoiceItem->price = $request->Price[$i];
            if(isset($request->tax[$i])) {
              $InvoiceItem->tax_status = 1;
              $InvoiceItem->tax_rate = $tax_rate;
              $InvoiceItem->price_in_tax = ($request->Quantity[$i] * $request->Price[$i]+($request->Quantity[$i] * $request->Price[$i] * ($tax_rate/100)));
            }
            else
            {
              $InvoiceItem->tax_status = 0;
              $InvoiceItem->tax_rate = 0.00;
              $InvoiceItem->price_in_tax = ($request->Quantity[$i] * $request->Price[$i]);
            }
            $InvoiceItem->save(); //stored in user table
            
          }
          $invoice = new Invoice;
          $admin_id = Session::get('admin_id');
          $invoice->user_id = $user_id_invoice;
          $invoice->invoice_id = $invoice_id;
          $invoice->tax_rate = $tax_rate;
          $invoice->total = $price_ex_tax;
          $invoice->memo = $memo;
          $invoice->admin_id = $admin_id;
          $invoice->save();
            $user_name = $name;
            $user_email = $email;
            $admin_users_email="hello@tier5.us";
            $activateLink = url('/').'/client/invoice/'.base64_encode($invoice_id);
            $sent = Mail::send('email.invoice_link', array('name'=>$user_name,'email'=>$user_email,'activate_link'=>$activateLink), 
            function($message) use ($admin_users_email, $user_email,$user_name)
            {
            $message->from($admin_users_email);
            $message->to($user_email, $user_name)->subject('Invoice From INVOICINGYOU.COM');
            });
          return redirect('/invoice-created/'.base64_encode($invoice_id));
      }
  }
  public function allRecords(){
      $admin_id = Session::get('admin_id');
      $user_details = Invoice::where('invoice_id', '!=',0)
      ->where('admin_id', $admin_id)
      ->with('invoice_items', 'user_details')
      ->get();
      return view('home.invoiceDetails',array('title'=>'Invoice System || Create Invoice'), compact('user_details'));
  }
  public function Dashboard(){
    $cax=[100, 25, 80, 81, 56, 55, 40];
    $admin_id = Session::get('admin_id');
    $adminImage = AdminDetail::where('admin_id', '=', $admin_id)->first(['image']);
    $adminImagecount = AdminDetail::where('admin_id', '=', $admin_id)->count();
    if ($adminImagecount == 0) {
      Session::put('image', '');
    }
    else {
      Session::put('image', $adminImage->image);
    }
    //check admin or super admin 
    $admin_info = Admin::where('id', $admin_id)->with('admin_details')->first();
    //dd($admin_info);
    if ($admin_info->admin_type == 1) {
      $all_invoice_details = Invoice::all();
      $all_admin_details = Admin::where('admin_type', 0)->get();
      //dd($all_admin_details);
      //graph data
      $population = Lava::DataTable();
      $population->addDateColumn('Year')
           ->addNumberColumn('Number of People')
           ->addRow(['2006', 623452])
           ->addRow(['2007', 685034])
           ->addRow(['2008', 716845])
           ->addRow(['2009', 757254])
           ->addRow(['2010', 778034])
           ->addRow(['2011', 792353])
           ->addRow(['2012', 839657])
           ->addRow(['2013', 842367])
           ->addRow(['2014', 0]);
      Lava::AreaChart('Population', $population, [
      'title' => 'Population Growth',
      'legend' => [
        'position' => 'in'
      ]
    ]);
      return view('super-admin.dashboard', compact('admin_info', 'all_admin_details', 'all_invoice_details'));
    }
    else
    {
      return view('home.dashboard',array('title'=>'Invoice System || Dashboard'), compact('cax', 'admin_info'));
    }
  }
  public function getProfile(){
    $gets=Session::get('admin_id');
    //print_r($gets->id);
    $Admin=AdminDetail::where('admin_id',$gets)->first();
    $Admincount=AdminDetail::where('admin_id',$gets)->count();
    return view('home.profile',array('title'=>'Invoice System || Profile'), compact('Admin','Admincount', 'image'));
  }
  public function updateProfile(Request $request){

      
      if($request->id!=""){
        $AdminDetail = AdminDetail::where('id',$request->id)->first();
          if($request->images)
          {
            $imgval=$request->images;
            $extension =$imgval->getClientOriginalExtension();
            $destinationPath = 'public/admin_new/';   // upload path
            $fileName = rand(111111111,999999999).'.'.$extension; // renameing image
            $imgval->move($destinationPath, $fileName); // uploading file to given path 
          }
          else
          {
             
            $fileName = $AdminDetail->image;
          }
      }else{

        $AdminDetail = new AdminDetail;
        if($request->images)
          {
            $imgval=$request->images;
            $extension =$imgval->getClientOriginalExtension();
            $destinationPath = 'public/admin_new/';   // upload path
            $fileName = rand(111111111,999999999).'.'.$extension; // renameing image
            $imgval->move($destinationPath, $fileName); // uploading file to given path 
          }
          else
          {
             
            $fileName = '';
          }
      }
      $AdminDetail->admin_id = Session::get('admin_id');
      $AdminDetail->name = $request->name;
      $AdminDetail->detail =$request->details;
      $AdminDetail->image =$fileName;
      $AdminDetail->save();
      if ($AdminDetail->save()) {
        Session::put('confirmation', 'Successfully Updated!');
        return redirect()->route('profile');
      }
      else
      {
        Session::put('confirmation', 'Error! updating Failed!');
        return redirect()->route('profile');
      }
      
    
      /*if($request->id!=""){
        if($request->images){
          $imgval=$request->images;
          $extension =$imgval->getClientOriginalExtension();
          $destinationPath = 'public/admin_new/';   // upload path
          $fileName = rand(111111111,999999999).'.'.$extension; // renameing image
          $imgval->move($destinationPath, $fileName); // uploading file to given path
        }
        
      
      $AdminDetail = AdminDetail::where('id',$request->id)->first();
      $AdminDetail->admin_id = Session::get('admin_id.id');
      $AdminDetail->name = $request->name;
      $AdminDetail->detail =$request->details;
      if($request->images){
        $AdminDetail->image =$fileName;
      }
      
      $AdminDetail->save();
      }else{
        //no image is there
        echo "yes";
        $imgval=$request->images;
        $extension =$imgval->getClientOriginalExtension();
        $destinationPath = 'public/admin_new/';   // upload path
        //$extension =$imgval->getClientOriginalExtension(); // getting image extension
        $fileName = rand(111111111,999999999).'.'.$extension; // renameing image
        $imgval->move($destinationPath, $fileName); // uploading file to given path
        $AdminDetail = new AdminDetail;
        $AdminDetail->admin_id = Session::get('admin_id.id');
        $AdminDetail->name = $request->name;
        $AdminDetail->detail =$request->details;
        $AdminDetail->image =$fileName;
        $AdminDetail->save(); 
      }*/
      
  }
  public function getIndex() {
    return view('index');
  }
  public function getRegister() {
    $super_admin_account = Admin::where('use_my_account', 1)->where('admin_type', 1)->with('payment_keys')->first();
      return view('register', compact('super_admin_account'));
  }
  public function postRegister(Request $request) {
    $name = $request->usr_name;
    $email = $request->usr_email;
    $password = $request->conf_pass;
    $membership_status = $request->select_membership;
    $stripeToken = $request->stripeToken;
    if ($stripeToken) 
    {
      //premium member here
      $return = $this->RegisterUser($name, $email, $password, $membership_status, $stripeToken);
      if ($return == 302) {
        return redirect()->route('register')->with('custom_err', 'Email Already exists try another one');
      }
      else
      {
        $last_inserted_id = $return;
        //now if last inserted id here proceed to payment
        if ($last_inserted_id) {
          $complete_register = $this->doPayment($last_inserted_id, $membership_status, $stripeToken);
          return $complete_register;
        }
        else
        {
          return redirect()->route('register')->with('custom_err', 'some error occured please try again later');
        }
      }
    }
    else
    {
      //basic member here
      $return = $this->RegisterUser($name, $email, $password, $membership_status, 0);
      if ($return == 302) 
      {
        return redirect()->route('register')->with('custom_err', 'Email Already exists try another one');
      }
      else
      {
        $last_inserted_id = $return;
        //now if last inserted id here proceed to login
        if ($last_inserted_id) {
          return redirect()->route('admin-login')->with('success_registration', 'You have successfully registerted.');
        }
        else
        {
         return redirect()->route('register')->with('custom_err', 'some error occured please try again later'); 
        }

      }
    }
  }

  private function RegisterUser($name, $email, $password, $membership_status, $stripeToken) {
      $search_email = Admin::where('email', $email)->first();
      if ($search_email) {
        return 302;
      } else {
        $admin = new Admin();
        $admin->email = $email;
        $admin->password = bcrypt($password);
        $admin->admin_type = 0;
        $admin->block_status = 0;
        $admin->payment_status = 0;
        $admin->use_my_account = 0;
        if ($admin->save()) {
          $admin_details = new AdminDetail();
          $admin_details->admin_id =$admin->id;
          $admin_details->name = $name;
          $admin_details->detail = 'Please write something about you';
          $admin_details->membership = $membership_status;
          if ($admin_details->save()) {
            return $admin_details->admin_id;
          }
        } else {
            return false;
        }
      }
  }
  private function doPayment($admin_id, $membership_status, $stripeToken) {
    $keys = Admin::where('use_my_account', 1)->where('admin_type', 1)->with('payment_keys')->first();
    $secret_key = $keys->payment_keys->key_second;
    $charge_amount = ($membership_status == 2 ? 10 : 20) ;
    $stripe = Stripe::make($secret_key);
    $search_admin = Admin::find($admin_id);
    $admin_email = $search_admin->email;
    $customer = $stripe->customers()->create([
      "source" => $stripeToken, // obtained from Stripe.js
      "plan" => $charge_amount == 10 ? 'pro10' : 'gold20',
      'email' => $admin_email,
    ]);
    //dd($customer['subscriptions']['data'][0]['status']);
    //charging a customer
    /*$charge = $stripe->charges()->create([
      'source' => $stripeToken,
      'currency' => 'USD',
      'amount'   => $charge_amount,
    ]);*/
    if($customer['subscriptions']['data'][0]['status']=="active")
    {
      $search_admin = Admin::find($admin_id);
      if ($search_admin) {
        $search_admin->payment_status = 1;
        $search_admin->save();
        return redirect()->route('admin-login')->with('success_registration', 'Hey! you have successfully registered as premium member');
      }
      else
      {
        return redirect()->route('admin-login')->with('fail', 'Failed to search an admin to update please check database');
      }
    } 
    else
    {
      return redirect()->route('register')->with('fail', 'Payment Failed please close the browser and open then try it again.');
    }

  }
  public function BanUser($id) {
    $id_ban = base64_decode($id);
    $block = Admin::find($id_ban);
    if ($block->block_status == 0) {
      $block->block_status = 1;
      if ($block->save()) {
        return redirect()->route('dashboard')->with('block_status', 'User has been blocked');
      }

    }
    elseif ($block->block_status == 1) {
      $block->block_status = 0;
      if ($block->save()) {
        return redirect()->route('dashboard')->with('block_status', 'User has been unblocked');
      }
    }
    else
    {
      return redirect()->route('dashboard')->with('block_status_er', 'Failed to block the user');
    }
  }
  public function postMembershipPayment($membership, $last_inserted_id) {
    //dd('err');
    $last_inserted_id = base64_decode($last_inserted_id); //taking last inserted id to update after payment

    $super_admin_account = Admin::where('use_my_account', 1)->where('admin_type', 1)->with('payment_keys')->first(); //fetching admin details to use one master account to recieve payments
    if ($super_admin_account!=null) {
      return view('postRegitrationPayment', compact('membership', 'last_inserted_id', 'super_admin_account'));
    }
    else
    {
      return redirect()->route('register')->with('custom_err', 'could not find an admin try to insert one');
    }
    
  }
  public function postPayment(Request $request) {
      $secret_key = $request->secret_key;
      $charge_amount = $request->stripeAmount;
      $admin_id = $request->last_inserted_id;
      $stripe = Stripe::make($secret_key);
      $charge = $stripe->charges()->create([
        'source' => $request->stripeToken,
        'currency' => 'USD',
        'amount'   => $charge_amount,
      ]);
      if($charge['status']=="succeeded")
      {
        $search_admin = Admin::find($admin_id);
        if ($search_admin) {
          $search_admin->payment_status = 1;
          $search_admin->save();
          return redirect()->route('admin-login')->with('success_registration', 'Hey! you have successfully registered as premium member');
        }
        else
        {
          return redirect()->route('admin-login')->with('fail', 'Failed to search an admin to update please check database');
        }
      } 
      else
      {
        return redirect()->route('register')->with('fail', 'Payment Failed please close the browser and open then try it again.');
      }
  }

}
