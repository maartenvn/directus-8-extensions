<?php

/*
	Author - PhilleepFlorence
	Description - Form Helper Functions
*/

namespace Directus\Custom\Helpers;

ini_set('max_execution_time', 240);
ini_set('memory_limit', '512M');
	
use Directus\Util\ArrayUtils;
use Directus\Util\DateUtils;
use Directus\Util\StringUtils;

class Form 
{	
	/*
		Checks if a CRM or API has been configured for a form action
		ARGUMENTS:
			$formname - form name or action
			$form - submitted form data to match against configured form options
	*/
	
	public static function CRM ($formname = NULL, $form = [])
	{
		if (!$formname || !is_string($formname)) 
		{
			return [
				"error" => true,
				"message" => Api::Responses('form.crm.form-name')
			];
		}
		
		$active = Api::Settings('mail.api.crm');		
		$active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
		
		if (!$formname || !is_string($formname)) 
		{
			return [
				"error" => true,
				"message" => Api::Responses('form.crm.api-inactive')
			];
		}		
		
		# Get options from App API Options
		    
	    $tableGateway = Api::TableGateway('app_api_options', true);	
	    $entries = $tableGateway->getItems([
		    "limit" => 1,
		    "filter" => [
			    "form" => $formname
		    ]
	    ]);
	    
	    $entries = ArrayUtils::get($entries, 'data', []);
	    $api = ArrayUtils::get($entries, '0.api.data');
	    $post = [];
	    
	    # Parse CRM options into post array
	    
	    foreach ($entries as $entry)
	    {
		    $input = ArrayUtils::get($entry, 'input');
		    $property = ArrayUtils::get($entry, 'property');
		    $value = ArrayUtils::get($entry, 'value') ?: ArrayUtils::get($form, $input) ?: '';
		    
		    if (ArrayUtils::get($entry, 'format') === 'string')
		    {
			    ArrayUtils::set($post, $property, $value);
		    }
		    elseif (ArrayUtils::get($entry, 'format') === 'array')
		    {
			    $key = ArrayUtils::get($post, $property, -1) + 1;
			    
			    ArrayUtils::set($post, "{$property}.{$key}", $value);
		    }
	    }
	    
	    if (!count($post) || !ArrayUtils::get($api, 'url') || !ArrayUtils::get($api, 'method')) 
	    {
		    return [
				"error" => true,
				"message" => str_replace('{{formname}}', $formname, Api::Responses('form.crm.configuration'))
			];
	    }
		
		return [
			"success" => true,
			"api" => $api,
			"post" => $post
		];
	}
	
	/*
		Form Mail Utility
		Send form data from an aoplication to one or more users
		PARAMETERS:
			form
				message - the HTML String Template
				subject - Mail Subject
				users - array list of user IDs
				template - the name of the template to use in App Templates Collection
	*/
	
	public static function Mail ($params = [], $debug)
	{		
		$headers = getallheaders();
		
	    $form = ArrayUtils::get($params, 'form');
	    $user = ArrayUtils::get($form, 'user');
	    $users = ArrayUtils::get($form, 'users');
	    
	    if (!$users && $user) $users = [ArrayUtils::get($user, 'id')];
	    
	    if (!$users) return [
		    "error" => "Missing array of user IDs!"
	    ];
	    
	    if (is_array($users)) $users = implode(',', $users);
	    
	    # Load the Application User Information if applicable
	    
	    $appuser = $user ?: ArrayUtils::get($headers, 'App-User');
	    
	    # Load all the users from the Application Users Collection
	    
	    $tableGateway = Api::TableGateway('app_users', true);
    
	    $entries =  $tableGateway->getItems([
		    "fields" => User::Fields("mail"),
		    "filter" => [
			    "id" => [
				    "in" => $users
			    ]
		    ]
	    ]);
	    
	    $users = ArrayUtils::get($entries, 'data'); 
	    
	    # Get the user making the update or request
	    
	    if ($appuser && !$user)
	    {
		    $appuser = intval($appuser);	    
	    
		    $entries =  $tableGateway->getItems([
			    "fields" => User::Fields("mail"),
			    "filter" => [
				    "id" => [
					    "eq" => $appuser
				    ]
			    ]
		    ]);
		    
		    $editor = ArrayUtils::get($entries, 'data.0');
		    $editor = User::Parse($editor);
		    
		    ArrayUtils::set($form, "editor", $editor);
	    }
        
	    $configuration = [
		    "configuration" => Api::configuration()
	    ];
	    
	    $response = [
		    "meta" => [
			    "mode" => "mail",
			    "collection" => "app_users",
			    "user" => $appuser
		    ],
		    "data" => []
	    ];
	    
	    $template = ArrayUtils::get($form, 'template') ?: 'template.plain';
	    $template = Mail::Template($template);
    
	    $template = array_merge($template, [
		    "title" => ArrayUtils::get($form, 'subject'),
		    "template" => ArrayUtils::get($form, 'message')
	    ]);
    
	    foreach ($users as $index => $user)
	    {
		    $email = ArrayUtils::get($user, 'email');
		    $user = User::Parse($user);
		    
		    if (!$email) continue;
		    
		    ArrayUtils::set($form, "user", $user);
		    		    
		    $data = array_merge($form, $configuration);
		    	    
		    $compiled = Mail::Mailbox($user, $data, $template);
		    
		    # Send Mail!
		    
		    $mail = Mail::Send($template, $user, ArrayUtils::get($configuration, 'configuration'), $compiled, $editor);
		    
		    array_push($response["data"], [
			    "user" => $user,
			    "mail" => $mail
		    ]);
	    }
	    
	    return $response;
	}
	
	/*
		Form Notify Utility
		Normalizer and Helper for CRM Notifications and Email Notifications
		PARAMETERS:
			formname - @String: The name of the form
			form - @Array: The form data object
			template - @String: The name of the template to use
			user - @Array: The athenticated user object if applicable (defaults to form data)
			response - @Array: The response object to update with results
			previews - @Array: The submitted form data previews to show in notification
	*/
	
	public static function Notify ($formname = '', $form = [], $template = '', $user = NULL, $response = [], $previews = [])
	{		
	    $user = $user ?: $form;
	    
	    # Send information to CRM - if applicable
		
		$crm = Form::CRM($formname, $form);
		
		if (ArrayUtils::get($crm, 'api'))
		{
			$api = ArrayUtils::get($crm, 'api');
		    $post = ArrayUtils::get($crm, 'post');
		    
		    $crm = Curl::Api($api, $post);
		    
		    ArrayUtils::set($response, 'meta.crm', $crm);
		} 
			
		# Check for and send notification using the $template
		
		$template = Mail::Template($template);
		
		if (is_array($template) && ArrayUtils::get($template, 'id'))
		{
			$user = User::Parse($user);
			
			$configuration = Api::Configuration();
			
			$data = [
			    "configuration" => $configuration,
			    "form" => $form,
			    "user" => $user,
			    "preview" => $previews
		    ];
		    
		    $compiled = Mail::Mailbox($user, $data, $template);
		    	    
		    ArrayUtils::set($form, 'subject', ( ArrayUtils::get($form, 'subject') ?: ArrayUtils::get($template, 'title') ));
		    
		    ArrayUtils::set($response, 'data.0', $form);
		    
		    $mail = Mail::Send($form, $user, $configuration, $compiled);
		    
		    $error = ArrayUtils::get($mail, 'error');
		    $success = ArrayUtils::get($mail, 'code');
		    $success = $success >= 200 && $success <= 299;
		    
		    if ($error) ArrayUtils::set($response, 'error', $error);
		    
		    ArrayUtils::set($response, 'meta.mail', $mail);
		    ArrayUtils::set($response, 'meta.success', $success);
		}
	    
	    return $response;
	}
	
	/*
		Form Submission Utility
		Submit Application Form Data to the App Mailbox
		COLLECTIONS:
			App Configuration
			App API Options
			App Mailbox
			App Templates
			App Users
		ARGUMENTS:
			@params - REQUIRED
				Form Data - See App MailBox for fields
	*/
	
	public static function Submit ($params = [], $debug)
	{
	    $headers = getallheaders();
	    $appuserdata = ArrayUtils::get($headers, 'App-User-Data');
	    
	    if (is_string($appuserdata)) $appuserdata = (array) json_decode($appuserdata);
	    
		$form = ArrayUtils::get($params, 'form', []);
		$previews = ArrayUtils::get($params, 'previews');
	    $user = ArrayUtils::get($params, 'user');
		
	    $formname = ArrayUtils::get($form, 'form');
	    $inbox = ArrayUtils::get($params, 'inbox');
	    $toEmail = ArrayUtils::get($form, 'email') ?: ArrayUtils::get($appuserdata, 'email');
	    $toName = ArrayUtils::get($form, 'name') ?: ArrayUtils::get($appuserdata, 'name');
	    $subject = ArrayUtils::get($form, 'subject') ?: ArrayUtils::get($form, 'notification.subject') ?: $formname;
	    $body = ArrayUtils::get($form, 'body') ?: ArrayUtils::get($form, 'message') ?: ArrayUtils::get($form, 'content') ?: $formname;
	    $from = ArrayUtils::get($form, 'from', []);
	    $template = ArrayUtils::get($params, 'template') ?: ArrayUtils::get($form, 'template') ?: $formname;
	    
	    $appuser = $user ?: ArrayUtils::get($headers, 'App-User');
	    
	    if (!$template || !$toEmail || !$subject || !$body) return [
		    "error" => true,
		    "message" => Api::Responses('form.submit.validation')
	    ];
        
	    # Normalize Form Body
	    
	    ArrayUtils::set($form, 'body', $body);
	    
	    # Parse First and Last Names if applicable
	    
	    if ($toName && !ArrayUtils::get($form, 'first_name'))
	    {
		    $toNames = explode(' ', $toName);
		    
		    ArrayUtils::set($form, 'first_name', reset($toNames));
		    ArrayUtils::set($form, 'last_name', end($toNames));
	    }
	    elseif (!$toName && ArrayUtils::get($form, 'first_name'))
	    {
		    $toName = trim(ArrayUtils::get($form, 'first_name') . ' ' . ArrayUtils::get($form, 'last_name'));
	    }
    
	    # Get IP Address if aplicable
	    
	    if (!ArrayUtils::get($form, 'ip_address')) ArrayUtils::set($form, 'ip_address', Server::ClientIP());
	    
	    # Set default response
	    
	    $response = [
		    "meta" => [
			    "collection" => "mailbox",
			    "mode" => "submit",
			    "success" => true
		    ]
	    ];
	    $insert = [];
	    
	    # Initialize zendb database connectors - Save Forms Object
	    
	    $tableGateway = Api::TableGateway('app_forms');
	    $formObject = $tableGateway->getItems([
		    "fields" => "id,name,slug,inputs.*",
		    "filter" => [
			    "slug" => $formname
		    ],
		    "limit" => 1
	    ]);
	    $formObject = ArrayUtils::get($formObject, 'data.0') ?: [];
	    $formInputs = ArrayUtils::get($formObject, 'inputs');
	    $formRows = [];
	    
	    if (is_array($formInputs))
	    {
		    foreach ($formInputs as $input)
		    {
			    $type = ArrayUtils::get($input, 'input_type');
			    
			    if (!ArrayUtils::get($input, 'form_type') || $type === "password") continue;
			    
			    $slug = ArrayUtils::get($input, 'property') ?: ArrayUtils::get($input, 'slug');
			    $value = ArrayUtils::get($form, $slug);
			    
			    if (!is_null($value)) array_push($formRows, [
				    "form" => ArrayUtils::get($formObject, 'id'),
				    "user" => $appuser,
				    "key" => ArrayUtils::get($input, 'name'),
				    "value" => $value,
				    "created" => date('Y-m-d H:m:s')
			    ]);
		    }
	    }
	    
	    # Insert Form Rows in Form Data Collection - Do not add sensitive data here!
	    
	    if (count($formRows))
	    {
		    $tableGateway = Api::TableGateway('app_forms_data', true);
		    
		    foreach ($formRows as $row)
		    {
			    $tableGateway->createRecord($row);
		    }
	    }
	    
	    # Initialize zendb database connectors - Save Form to Mailbox
	    
	    $tableGateway = Api::TableGateway('app_mailbox', true);
	    $tableSchema = $tableGateway->getTableSchema();
	    
	    $fields = $tableSchema->getFieldsName();
	    
	    # Save form to App Forms Data - INBOX
    
	    foreach ($fields as $field)
	    {
		    $value = ArrayUtils::get($form, $field);
		    
		    if (is_array($value)) $value = implode(", ", $value);
		    
		    if ($value) ArrayUtils::set($insert, $field, $value);
	    }
        
		if ($inbox) $tableGateway->createRecord($insert);
		
		# Save form data email address to app users if not exist!
		
		$tableGateway = Api::TableGateway('app_users', true);
	    $entries = $tableGateway->getItems([
		    "limit" => 1,
		    "status" => "draft,published",
		    "filter" => [
			    "email" => [
				    "eq" => $toEmail
			    ]
		    ]
	    ]);
	    $entries = ArrayUtils::get($entries, "data.0");
	    
	    if (!$entries)
	    {
		    $entries = [];
		    $tableSchema = $tableGateway->getTableSchema();
		    $fields = $tableSchema->getFieldsName();
	    
		    foreach ($fields as $field)
		    {
			    $value = ArrayUtils::get($form, $field);
			    
			    if (is_array($value)) $value = implode(", ", $value);
			    
			    if ($value) ArrayUtils::set($entries, $field, $value);
		    }
		    
		    $tableGateway->createRecord($entries);
	    }
	    
	    # Get user object if ID is sent

	    if (is_numeric($user)) $user = User::user($user);
	    elseif (is_array($entries)) $user = User::Parse($entries);
	    
	    $response = Form::Notify($formname, $form, $template, $user, $response, $previews);
	    
	    return $response;
	}
}