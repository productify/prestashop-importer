<?php

if (!defined('_PS_VERSION_'))
	exit;

class ProductifyUpdateModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		parent::initContent();
        
        $result = Productify::startCronUpdate();
        
        if($result != null)
        {
            //echo "send email";
            $cron_status = Db::getInstance()->executeS("select * from "._DB_PREFIX_."productify_import where import_id = $result");
            $detail = $cron_status[0];
            $added = $detail['imported'];
            $failed = $detail['failed'];
            $updated_prds = $detail['updated_products'];
            $updated_prds = explode(", ",$updated_prds);
            if($updated_prds[0]!='')
            {
                $total_updated = count($updated_prds);
                $total_added = $added - $total_updated;
            }
            else
            {
                $total_added = $added;
                $total_updated = 0;
            }
            
            $total_failed = $failed;
            
            if($total_updated > 0)
            {
                $updated_products_string = "<br /><br /><strong>Updated products:</strong><br />";
                $updated_products_string .= implode("<br />",$updated_prds);
                $updated_products_string .= "<br /><br />";
            }
            else
            {
                $updated_products_string = "<br /><br />";
            }
                                       
            $message = "<html>
                        <head>
                            <title>Data imported successfully</title>
                        </head>
                        <body>
                            <p>
                            Hi there,<br /><br />
                            The Productify Import has been successfully completed.<br /><br />
                            <strong>Details:</strong> <br />
                            Total records imported: ".$total_added."<br />
                            Total records updated: ".$total_updated."<br />
                            Total records failed: ".$total_failed.$updated_products_string."
                            Please login into your store to see the imported Products.
                            Note, depending on preferences chosen, you may have to enable the Products 
                            or Categories within the admin to display the products in your store.
                            <br /><br />
                            Regards
                            </p>
                        </body>
                        </html>";
            $to = $detail['email'];
            $subject = "Data Imported Successfully";
            
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= "From: Productify.com<noreply@productify.com> \r\n";
            
            $sendig_mail = mail($to,$subject,$message,$headers);
            //$id_lang = new Language((int)(Configuration::get('PS_LANG_DEFAULT')));
            //Mail::Send($id_lang,$message,$subject,'',$to,'','noreply@productify.com','Productify.com');
            
        }
        else
        {
            
        }
    }
    
}

?>