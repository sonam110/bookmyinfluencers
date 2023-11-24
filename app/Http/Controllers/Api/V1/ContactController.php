<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;
use Auth;
use Exception;
use DB;
use App\Models\Contact;
use Mail;
use App\Mail\ContactMail;

class ContactController extends Controller {

    public function saveContact(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                        'fullname' => 'required',
                        'email' => 'required',
                        'message' => 'required',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $contact = new Contact;
            $contact->fullname = $request->fullname;
            $contact->email = $request->email;
            $contact->who_are_you = $request->who_are_you;
            $contact->skype_whats_app = $request->skype_whats_app;
            $contact->message = $request->message;
            $contact->ip_address = $request->ip();
            $contact->save();
            if ($contact) {
                $content = [
                    'contact' => Contact::where('id', $contact->id)->first()
                ];
                if (env('IS_MAIL_ENABLE', true) == true) {
                    $recevier = Mail::to(env('MANAGER_MAIL', 'abhishek@prchitects.com'))->send(new ContactMail($content));
                }
                transactionHistory($contact->id, '', '9', 'Contact Us', '3', '0', '0', '0', '1', '', '1', 'contacts', $contact->id,'INR',NULL);

                return prepareResult(true, 'Thank you for getting in touch, we will get back to you as soon as possible..', $contact, $this->success);
            } else {
                return prepareResult(true, 'Opps! Something went wrong', [], $this->internal_server_error);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

}
