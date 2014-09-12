<?php
if (!defined('_PS_VERSION_'))
    exit;

class Productify extends Module
{
    public function __construct()
    {
        $this->name = 'productify';
        $this->tab = 'other_modules';
        $this->version = '1.0';
        $this->author = '<a href="http://productify.com" target="_blank">Productify.com</a>';
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Productify Module');
        $this->description = $this->l('This module makes it easy to import products from Productify.com');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->_checkContent();

        $this->context->smarty->assign('module_name', $this->name);
    }

    public function _checkContent()
    {
        if (!Configuration::get('MOD_PRODUCTIFY_URL') &&
            !Configuration::get('MOD_PRODUCTIFY_COLOR'))
            $this->warning = $this->l('You need to configure this module.');
    }

    public function install()
    {
        Db::getInstance()->execute("CREATE TABLE IF NOT EXISTS `" ._DB_PREFIX_ . "productify_import` (
          `import_id` int(11) NOT NULL AUTO_INCREMENT,
          `date_added`  datetime NOT NULL,
          `modified_time` TIMESTAMP NOT NULL,
          `status` tinyint(1) NOT NULL,
          `url` varchar(255) NOT NULL,
          `skus` text NOT NULL,
          `email` varchar(255) NOT NULL,
          `images` tinyint(1) NOT NULL,
          `enable_products` tinyint(1) NOT NULL,
          `total_import` int(11),
          `imported` int(11),
          `failed` int(11),
          `updated_products` text, 
          `processing` tinyint(1),
          PRIMARY KEY (`import_id`)
        )");
        if (!parent::install() ||
            /*!$this->registerHook('displayHeader') ||
            !$this->registerHook('displayLeftColumn') ||
            !$this->registerHook('displayRightColumn') ||
            !$this->registerHook('displayFooter') ||*/
            !$this->_createContent())
            return false;
        return true;
    }

    public function uninstall()
    {
       Db::getInstance()->execute("DROP TABLE IF EXISTS`" . _DB_PREFIX_ . "productify_import` ");
        if (!parent::uninstall() ||
            !$this->_deleteContent())
            return false;
        return true;
    }

    public function _createContent()
    {
        if (!Configuration::updateValue('MOD_PRODUCTIFY_URL', '') ||
            !Configuration::updateValue('MOD_PRODUCTIFY_COLOR', ''))
            return false;
        return true;
    }

    public function _deleteContent()
    {
        if (!Configuration::deleteByName('MOD_PRODUCTIFY_URL'))
            return false;
        return true;

    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path.'css/style.css', 'all');
        $this->context->controller->addJS($this->_path.'js/script.js', 'all');
    }

    public function hookDisplayLeftColumn()
    {
        $this->context->smarty->assign(array(
            'placement' => 'left',
        ));

        return $this->display(__FILE__, 'left.tpl');
    }

    public function hookDisplayRightColumn()
    {
        $this->context->smarty->assign(array(
            'placement' => 'right',
        ));

        return $this->display(__FILE__, 'right.tpl');
    }

    public function hookDisplayFooter()
    {
        $this->context->smarty->assign(array(
            'module_link' => $this->context->link->getModuleLink('productify', 'details'),
        ));

        return $this->display(__FILE__, 'footer.tpl');
    }


    //tells Prestashop that our module needs a configuration page
    public function getContent()
    {
        $this->context->controller->addCSS($this->_path.'css/style.css', 'all');
        $this->context->controller->addCSS($this->_path.'css/dataTables.bootstrap.css', 'all');

        $this->context->controller->addJS($this->_path.'js/jquery.dataTables.js', 'all');
        $this->context->controller->addJS($this->_path.'js/dataTables.bootstrap.js', 'all');
        $this->context->controller->addJS($this->_path.'js/dataTables.bootstrapPagination.js', 'all');

    $message = '';

        if (Tools::isSubmit('submit_'.$this->name))
        {
            //$feed_url = $_POST['MOD_PRODUCTIFY_URL'];
            $this->_deleteContent();
            $this->_saveContent();
            $message = $this->_select();
            
            $url = $_POST['MOD_PRODUCTIFY_URL'];
            $xml = simplexml_load_file($url);

            if ($xml){
                foreach ($xml->products as $product) {
                    foreach ($product as $product_detail) {
                        $details[] = $product_detail;
                    }
                }
                $message = $details;
                $xml = $message;
            }
            else
            {
                $message = $this->displayError($this->l('The feed URL entered is incorrect. Feed not found or invalid format.'));
                $this->_displayContent($message);
                return $this->display(__FILE__, 'settings.tpl');
                //$this->errors[] = Tools::displayError('The feed URL entered is incorrect. Feed not found or invalid format.');
                //exit;
            }
            
            $this->_displayContent($message);
            //$xml = $this->_parseXML(Configuration::get('MOD_PRODUCTIFY_URL'));

            $this->context->smarty->assign('xml', $xml);
            //$xml = simplexml_load_file(Tools::getValue('MOD_PRODUCTIFY_URL')) or die('There seems to be a problem loading xml feed. Please try again later.');
            return $this->display(__FILE__, 'views/templates/admin/select.tpl');
        }

        elseif (Tools::isSubmit('submit_select'))
        {
            //print_r($_POST);exit;
            error_reporting(E_ALL);

            //include(dirname(__FILE__).'/config/config.inc.php');
            //include(dirname(__FILE__).'/init.php');

            $xml = simplexml_load_file(Configuration::get('MOD_PRODUCTIFY_URL')) or die('Cannot load XML file. Please try again later.');

            $skulist = $_POST['product'];

            $skus = array_unique($skulist);
            $active_products = $_POST['active'];
            $import_images = $_POST['images'];
            
            
            //if(1)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
            {
                //this is to take place when on windows server.
                $count = 0;
                $total_update = 0;
                $success = 0;
                $failed = 0;
                foreach ($xml->products->product as $prd) {
                    //print_r($prd);
                    //echo "<br />";
    
                    $product = array(
                        "product_code" => "$prd->product_code",
                        "name" => "$prd->product_name",
                        "brand" => "$prd->brand",
                        "short_description" => "$prd->short_description",
                        "detail_description" => "$prd->detailed_description",
                        "active_prodcuts" => "$active_products",
                        "import_images" => "$import_images"
                    );
    
                    $categories = $prd->categories->category;
                    $product['category'] = "$categories";
                    $images = array();
                    foreach ($prd->media->image_url as $img) {
                        //print_r($img);
                        $image_default = "false";
                        foreach ($img->attributes() as $a => $b) {
                            $image_default = "$b";
                        }
                        $images[] = array("default" => "$image_default", "image_url" => "$img");
                    }
                    $product['media'] = $images;
                    $upload = (!isset($upload) || $upload != 1)?0:1;
                    
                    $sku = array();
                    $i=0;
                    foreach ($prd->skus->sku as $s) {
                        //print_r($s);
                        $variants = array();
                        $prd_sku = "$s->id";
                        
                        if (in_array($prd_sku, $skus)) {
                            $upload = 1;
                            $sku[$i]["id"] = "$prd_sku";
                            $sku[$i]["sale_price"] = "$s->sale_price";
                            $sku[$i]["retail_price"] = "$s->retail_price";
                            $sku[$i]["stock"] = "$s->stock";
                            $sku[$i]["ean"] = "$s->ean";
                            $sku[$i]["upc"] = "$s->upc";
                            $sku[$i]["weight"] = "$s->weight";
                            foreach ($s->variants->variant as $var) {
                                foreach ($var->attributes() as $a => $v) {
                                    $variants[] = array(
                                        'label' => "$v",
                                        'value' => "$var",
                                        'stock' => "$s->stock",
                                        'ean' => "$s->ean",
                                        'upc'=> "$s->upc",
                                        'weight' => "$s->weight",
                                        'sku' => "$prd_sku"
                                    );
                                }
                            }
                                
                                $sku[$i]['variants'][] = $variants;
                                
    
                                // Krita to authenticate only original skus
                                if (($key = array_search($prd_sku, $skus)) !== false) {
                                    unset($skus[$key]);
                                }
                        }
                    }
                    if($upload == 1)
                        {
                            $product['sku'] = $sku;
                            //add the product to the database
                            $total_update += $this->check_product_exists($product);
                            $imported= $this->importProducts($product);
                            
                            if($imported == 1)
                            {
                                $success++;
                            }
                            else
                            {
                                $failed++;
                            }
                            
                            $upload = 0;
                            //Krita Please add this in respective place or it'll hunt you :)
                            if (Configuration::get('PS_SEARCH_INDEXATION'))
                            Search::indexation(true);
                        }
                    if(count($skus) == 0)
                    {
                        $total_added = $success - $total_update;
                        $total_failed = $failed;
                        $to = $_POST['mail'];
                        $subject = "Data Imported Successfully";
                        $headers = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                        $headers .= "From: Productify.com<noreply@productify.com> \r\n";
                        
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
                                        Total records updated: ".$total_update."<br />
                                        Total records failed: ".$total_failed."<br /><br />
                                        Please login into your store to see the imported Products.
                                        Note, depending on preferences chosen, you may have to enable the Products 
                                        or Categories within the admin to display the products in your store.
                                        <br /><br />
                                        Regards
                                        </p>
                                    </body>
                                    </html>";
                        
                        $sendig_mail = mail($to,$subject,$message,$headers);//exit;
                        break;
                    }
                }
                $message = $this->displayConfirmation($this->l('Congratulations! '.$total_added." product(s) have been imported and $total_update has been updated."));
                $this->_displayContent($message);
                //this was done on windows server 
            }
            else
            {
                $skus = json_encode($skus);
                $email = $_POST['mail'];
                $date_time = date("Y-m-d H:i:s");
                $url = Configuration::get('MOD_PRODUCTIFY_URL');
                
                $add_to_import = Db::getInstance()->execute("Insert into "._DB_PREFIX_."productify_import set 
                                                                    date_added = '$date_time', 
                                                                    status = 1, 
                                                                    url = '$url', 
                                                                    skus = '$skus', 
                                                                    email = '$email', 
                                                                    images = $import_images, 
                                                                    enable_products = $active_products,
                                                                    updated_products = '',
                                                                    imported = 0, 
                                                                    failed = 0");
                if($add_to_import)
                {
                    $cron_url = _PS_BASE_URL_.__PS_BASE_URI__."module/productify/update";
                    
                    //exec("curl --silent $cron_url");
                    
                    $output = shell_exec('crontab -l');
                    
                    if(strpos($output,$cron_url) === false)
                    {
                        file_put_contents('/tmp/crontab.txt', $output."*/1 * * * * wget -q -O /dev/null $cron_url".PHP_EOL);
                        exec('crontab /tmp/crontab.txt');
                    }
                    $message = $this->displayConfirmation($this->l("Added to cron"));
                    $this->_displayContent($message);
                }
                else
                {
                    $message = $this->displayError($this->l("Failed to run the import process, Please try again!"));
                    $this->_displayContent($message);
                }
            }
                
            // end of adding to cron
            
            return $this->display(__FILE__, 'views/templates/admin/process.tpl');
        }

        else
        {
            $this->_displayContent($message);
            return $this->display(__FILE__, 'settings.tpl');
        }

    }

    public function importProducts($product,$imp_id = 0)
    {
        ini_set('memory_limit','256M');
        ini_set('upload_max_filesize','256M');
        /* Add a new product */
        $object = new Product();
        $object->price = (float)$product['sku'][0]['retail_price']/100;
        //$object->wholesale_price = $product['sku']['sale_price']/100;
        $object->reference = $product['sku'][0]['id'];
        
        $object->id_tax_rules_group = 0;
        $object->name = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $product['name']);
        $object->description_short = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $product['short_description']);
        $object->description = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $product['detail_description']);
        $object->link_rewrite = array((int)(Configuration::get('PS_LANG_DEFAULT')) => Tools::link_rewrite($product['name']));
        $object->id_manufacturer = 0;
        $object->id_supplier = 0;
        $object->ean13 = $product['sku'][0]['ean'];
        //$objet->upc = $product['sku'][0]['upc'];
        
        //For Meta tags
        $object->meta_description = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $product['short_description']);
        $object->meta_title = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $product['name']);

        $weight_units = preg_split('#(?<=\d)(?=[a-z])#i', $product['sku'][0]['weight']);
        if($weight_units[1] == "g")
        {
            $weight =  0.00220462 * $weight_units[0];
        }
        elseif(strtolower($weight_units[1]) == "kg")
        {
            $weight =  2.20462 * $weight_units[0];
        }
        
        $object->weight = (float)$weight;

        
        $object->out_of_stock = 0;
        $object->minimal_quantity = 1;

        $object->additional_shipping_cost = 0;
        $object->wholesale_price = 0;
        //$object->active = (int)$product['active_prodcuts'];
        $object->active = 1;

        $object->date_add = date('Y-m-d H:i:s');

        $object->available_for_order = 1;
        $object->show_price = 1;
        $object->on_sale = 0;
        $object->meta_keywords = 'test';

        //for category addition
        $values = $product['category'];
        $pieces = explode(">", $values);

        $cat = Productify::checkCategory($pieces['0'], 0);
        //echo "this is category <br /><pre>";print_r($cat);echo "</pre>";
        
        if ($pieces['1']) {
            $cats = Productify::checkCategory($pieces['1'], $cat[0]);
            foreach ($cats as $c) {
                array_push($cat, $c);
            }
        }

        $object->id_category_default = $cat[1];
        $object->category = array((int)(Configuration::get('PS_LANG_DEFAULT')) => Tools::link_rewrite($pieces[1]));
        $object->categories=$cat;
        //manufacturer
        $manufacturer = $product['brand'];
        
        if($manufacturer != "")
        {
            $object->id_manufacturer = (int)Productify::createManufacturer($manufacturer);
            $object->manufacturer_name = $manufacturer;
        }
        
        //for variants
        $attributes = array();
        foreach($product['sku'] as $sk)
        {
            foreach($sk['variants'] as $v)
            {
               $attributes[] = array("label"=>$v[0]['label'],"value"=>$v[0]['value'],"ean"=>$v[0]['ean'],"upc"=>$v[0]['upc'],"qty"=>$v[0]['stock'],"sku"=>$v[0]['sku'],"weight"=>$v[0]['weight']);
            }
        }
        //print_r($attributes);
        $attr_options = array();
        foreach($attributes as $at)
        {
            $attr_options[] = Productify::createAttr($at);
        }
        
        //add images
        /*foreach($product['media'] as $img)
        {
            $object->addImageToMediaGallery($img['url'],'image',true,false);
        }*/

        $object->add();
        if($object->save())
        {
            Productify::addSalePrice($object->id,(float)$product['sku'][0]['sale_price']/100);
            Productify::addProductAttributeCombination($object->id,$attr_options);
            
            if($imp_id != 0)
            {
                Productify::update_database($imp_id);
            }
            
            
            $imp_product = new Product((int)$object->id);
            $imp_product->addToCategories($object->categories);
            $imp_product->updateCategories($object->categories,1);
            $imp_product->category = Category::getLinkRewrite((int)$object->id_category_default, (int)(Configuration::get('PS_LANG_DEFAULT')));
            foreach($object->categories as $cat)
            {
                //echo $cat;
                $imp_product->cleanPositions($cat);
                //echo "<br />";
            }
            if((int)$product['active_prodcuts'] != 1)
            {
                $imp_product->active = 0;
            }
            $imp_product->save();
            //$object->save();
            
            
            //image import
            if($product['import_images'] == 1)
            {
                Productify::AddImages($product['media'], $object);
            }

            //$this->AfterAdd();
            return 1;
        }
        else{
            return 0;
        }
        //$count++;
    }

    public function checkCategory($name, $par_id)
    {
        if ($par_id == 0){
            $par_id = Configuration::get('PS_HOME_CATEGORY');
        }

        //$id_lang = (int)(Configuration::get('PS_LANG_DEFAULT'));
        //echo $id_lang;
        $catid = array();
        
        //$categoryAlreadyCreated = Category::searchByName($id_lang, $name, true);
        $categoryAlreadyCreated = Category::searchByNameAndParentCategoryId((int)(Configuration::get('PS_LANG_DEFAULT')), $name, $par_id);
        //$categoryAlreadyCreated = Category::searchByNameAndParentCategoryId($defaultLanguageId, $category->name[$defaultLanguageId], $category->id_parent);

        // If category already in base, get id category back
        if ($categoryAlreadyCreated)
        {
            //$catMoved[$category->id] = intval($categoryAlreadyCreated['id_category']);
            $parent_id = $categoryAlreadyCreated['id_category'];
            array_push($catid, $parent_id);
        }
        else
        {
            //if not found
            Productify::createCategory($name, $par_id);
            $categoryAlreadyCreated = Category::searchByNameAndParentCategoryId((int)(Configuration::get('PS_LANG_DEFAULT')), $name, $par_id);
            $parent_id = $categoryAlreadyCreated['id_category'];
            array_push($catid, $parent_id);
            
        }
        return $catid;

    }

    public function createCategory($name, $parent_id)
    {
        $object = new Category();
        $link = Tools::link_rewrite($name);
        $object->name = array();
        $object->link_rewrite = array();
        foreach (Language::getLanguages(false) as $lang){
            $object->name[(int)$lang['id_lang']] = $name;
            $object->link_rewrite[(int)$lang['id_lang']] = $link;
        }
        $object->id_parent = $parent_id;
        $object->add();        

        //returns id of the saved category
        //return $object->save();
    }

    public function AddImages($images, $object)
    {
        $defaultLanguageId = new Language((int)(Configuration::get('PS_LANG_DEFAULT')));
        if(!is_array($images) OR count($images)==0)
            return;

        $_warnings = array();
        $_errors = array();
        $productHasImages = (bool)Image::getImages(1, (int)($this->object->id));
        foreach ($images as $url)
        {
            if (!empty($url['image_url']))
            {
                $image = new Image();
                $image->id_product = (int)($object->id);
                $image->position = Image::getHighestPosition($object->id) + 1;
                $image->cover = ($url['default'] == 'true') ? true : false;
                $image->legend = self::createMultiLangField($object->name[1]);
                if (($fieldError = $image->validateFields(UNFRIENDLY_ERROR, true)) === true AND ($langFieldError = $image->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true AND $image->add())
                {
                    $image->associateTo($object);
                    if (!self::copyImg($object->id, $image->id, $url['image_url']))
                        $_warnings[] = Tools::displayError('Error copying image: ').$url['image_url'];
                }
                else
                {
                    $_warnings[] = $image->legend[$defaultLanguageId].(isset($image->id_product) ? ' ('.$image->id_product.')' : '').' '.Tools::displayError('cannot be saved');
                    $_errors[] = ($fieldError !== true ? $fieldError : '').($langFieldError !== true ? $langFieldError : '').mysql_error();
                }
            }
        }

    }

    public static function createMultiLangField($field)
    {
        $languages = Language::getLanguages(false);
        $res = array();
        foreach ($languages AS $lang)
            $res[$lang['id_lang']] = $field;
        return $res;
    }

    public static function copyImg($id_entity, $id_image = NULL, $url, $entity = 'products')
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));

        switch($entity)
        {
            default:
            case 'products':
            $image_obj = new Image($id_image);
            $path = $image_obj->getPathForCreation();
                //$path = _PS_PROD_IMG_DIR_.(int)($id_entity).'-'.(int)($id_image);
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_.(int)($id_entity);
                break;
        }

        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (!ImageManager::checkImageMemoryLimit($url))
            return false;

        if (@copy($url, $tmpfile))
        {
            //imageResize($tmpfile, $path.'.jpg');
            self::removeWhiteSpace($tmpfile, $path.'.jpg');
            $path2 = _PS_PROD_IMG_DIR_.(int)($id_entity).'-'.(int)($id_image);
            $newimage = $path2.'.jpg';
            $imagesTypes = ImageType::getImagesTypes($entity);
            foreach ($imagesTypes AS $k => $imageType){
                ImageManager::resize($newimage, $path.'-'.stripslashes($imageType['name']).'.jpg', $imageType['width'], $imageType['height']);
            }
            if (in_array($imageType['id_image_type'], $watermark_types))
                Module::hookExec('watermark', array('id_image' => $id_image, 'id_product' => $id_entity));
        }
        else
        {
            unlink($tmpfile);
            return false;
        }

        unlink($tmpfile);
        return true;
    }

    public static function removeWhiteSpace($from, $to){
        $img = imagecreatefromjpeg($from);

        //find the size of the borders
        $b_top = 0;
        $b_btm = 0;
        $b_lft = 0;
        $b_rt = 0;

        //top
        for(; $b_top < imagesy($img); ++$b_top) {
            for($x = 0; $x < imagesx($img); ++$x) {
                if(imagecolorat($img, $x, $b_top) != 0xFFFFFF) {
                    break 2; //out of the 'top' loop
                }
            }
        }

        //bottom
        for(; $b_btm < imagesy($img); ++$b_btm) {
            for($x = 0; $x < imagesx($img); ++$x) {
                if(imagecolorat($img, $x, imagesy($img) - $b_btm-1) != 0xFFFFFF) {
                    break 2; //out of the 'bottom' loop
                }
            }
        }

        //left
        for(; $b_lft < imagesx($img); ++$b_lft) {
            for($y = 0; $y < imagesy($img); ++$y) {
                if(imagecolorat($img, $b_lft, $y) != 0xFFFFFF) {
                    break 2; //out of the 'left' loop
                }
            }
        }

        //right
        for(; $b_rt < imagesx($img); ++$b_rt) {
            for($y = 0; $y < imagesy($img); ++$y) {
                if(imagecolorat($img, imagesx($img) - $b_rt-1, $y) != 0xFFFFFF) {
                    break 2; //out of the 'right' loop
                }
            }
        }

        //copy the contents, excluding the border
        $newimg = imagecreatetruecolor(
            imagesx($img)-($b_lft+$b_rt), imagesy($img)-($b_top+$b_btm));

        imagecopy($newimg, $img, 0, 0, $b_lft, $b_top, imagesx($newimg), imagesy($newimg));
        imagejpeg($newimg,$to);
    }

    public function _select()
    {
        if (Tools::getValue('MOD_PRODUCTIFY_URL'))
        {
            $message = $this->displayConfirmation($this->l('The XML has been successfully parsed.'));
        }

        else
            $message = $this->displayError($this->l('There was an error while saving your settings'));

        return $message;
    }


    public function _saveContent()
    {
        $message = 'This is message of get save function.';

        if (Configuration::updateValue('MOD_PRODUCTIFY_URL', Tools::getValue('MOD_PRODUCTIFY_URL')))
            $message = $this->displayConfirmation($this->l('Your settings have been saved'));
        else
            $message = $this->displayError($this->l('There was an error while saving your settings'));

        return $message;
    }

    public function _displayContent($message)
    {
        $this->context->smarty->assign(array(
            'message' => $message,
            'MOD_PRODUCTIFY_URL' => Configuration::get('MOD_PRODUCTIFY_URL')
        ));
    }

    public function _parseXML($feed)
    {
        $xml = simplexml_load_file($feed);

        if ($xml){
            foreach ($xml->products as $product) {
                foreach ($product as $product_detail) {
                    $details[] = $product_detail;
                }
            }
            $message = $details;
        }
        else
            $message = $this->displayError($this->l('There seems to be a problem loading xml feed. Please try again later.'));

        return $message;

    }
    
    
    public function createManufacturer($name)
    {
        $manufacturer = Manufacturer::getIdByName($name);
        
        //echo $manufacturer;die();
        if($manufacturer !== false)
        {
            return $manufacturer;
        }
        else
        {
            $object = new Manufacturer();
            $link = Tools::link_rewrite($name);
            $object->name = array();
            $object->link_rewrite = array();
            foreach (Language::getLanguages(false) as $lang){
                $object->name= $name;
                $object->active = 1;
            }
            $object->save();
            return $this->createManufacturer($name);
        }
        
    }
    
    public function addSalePrice($product_id,$sale_price)
    {
        $object = new SpecificPrice();
        $object->id_product = $product_id;
        $object->price = $sale_price;
        $object->id_shop = 0;
        $object->id_cart = 0;
        $object->id_product_attribute = 0;
        $object->id_currency = 0;
        $object->id_specific_price_rule = 0;
        $object->id_country = 0;
        $object->id_group = 0;
        $object->id_customer = 0;
        $object->from_quantity = 1;
        $object->reduction = 0;
        $object->reduction_type = 0;
        $object->from = 0;
        $object->to = 0;
        
        $object->save();
    }
    
    
    public function createAttr($attr)
    {
        $id_lang = new Language((int)(Configuration::get('PS_LANG_DEFAULT')));
        $attributes_list = AttributeGroup::getAttributesGroups((int)(Configuration::get('PS_LANG_DEFAULT')));
        $attr_group_id = null;
        foreach($attributes_list as $attribut)
        {
            //print_r($attribut);
            if($attr['label'] == $attribut['public_name'])
            {
                $attr_group_id = $attribut['id_attribute_group'];
                break;
            }
        }
        if($attr_group_id == null)
        {
            $object = new AttributeGroup();
            $object->name = array((int)(Configuration::get('PS_LANG_DEFAULT')) => ucfirst($attr['label']));
            $object->public_name = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $attr['label']);
            $object->group_type = "select";
            $object->is_color_group = 0;
            
            $object->save();
            
            $attr_group_id = $this->attributeGroupId($attr);
        }
        
            //echo "adding attribute options";
            $attribute_opt = AttributeGroup::getAttributes((int)(Configuration::get('PS_LANG_DEFAULT')),$attr_group_id);
            if(count($attribute_opt) == 0)
            {
                $object = new Attribute();
                $object->name = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $attr['value']);
                $object->id_attribute_group = (int)$attr_group_id;
                
                $object->save();
                
                $attribute_id = $this->attributeId($attr,$attr_group_id);
            }
            else
            {
                $attribute_id = null;
                foreach($attribute_opt as $atr_opt)
                {
                    //print_r($atr_opt);
                    if($atr_opt['name'] == $attr['value'])
                    {
                        $attribute_id = $atr_opt['id_attribute'];
                        break;
                    }
                }
                if($attribute_id == null)
                {
                    $object = new Attribute();
                    $object->name = array((int)(Configuration::get('PS_LANG_DEFAULT')) => $attr['value']);
                    $object->id_attribute_group = (int)$attr_group_id;
                    
                    $object->save();
                    
                    $attribute_id = $this->attributeId($attr,$attr_group_id);
                }
            }
                
        
        
        return array("attribute_group"=>$attr_group_id,"attribute_id"=>$attribute_id,"ean"=>$attr['ean'],"upc"=>$attr['upc'],"qty"=>$attr['qty'],"sku"=>$attr['sku'],"weight"=>$attr['weight']);
    }
    public function attributeGroupId($attr)
    {
        $id_lang = new Language((int)(Configuration::get('PS_LANG_DEFAULT')));
        $attributes_list = AttributeGroup::getAttributesGroups($id_lang);
        $attr_group_id = null;
        foreach($attributes_list as $attribut)
        {
            //print_r($attribut);
            if($attr['label'] == $attribut['public_name'])
            {
                return $attribut['id_attribute_group'];
                
            }
        }
    }
    
    public function attributeId($attr,$attr_group_id)
    {
        $id_lang = new Language((int)(Configuration::get('PS_LANG_DEFAULT')));
        $attribute_opt = AttributeGroup::getAttributes((int)(Configuration::get('PS_LANG_DEFAULT')),$attr_group_id);
        foreach($attribute_opt as $atr_opt)
        {
            //print_r($atr_opt);
            if($atr_opt['name'] == $attr['value'])
            {
                return $atr_opt['id_attribute'];
                
            }
        }
    }
    
    public function addProductAttributeCombination($id,$attr_options)
    {
        $id_attributes = array();
        $combinations = array();
        $quantity = array();
        $prd = new Product((int)$id);
        $i=1;
        foreach($attr_options as $at)
        {
            
            $weight_units = preg_split('#(?<=\d)(?=[a-z])#i', $at['weight']);
            if($weight_units[1] == "g")
            {
                $weight =  0.00220462 * $weight_units[0];
            }
            elseif(strtolower($weight_units[1]) == "kg")
            {
                $weight =  2.20462 * $weight_units[0];
            }
                    
            $object = new Combination();
            $object->id_product = $id;
            $object->ean13 = $at['ean'];
            $object->upc = $at['upc'];
            $object->quantity = (int)$at['qty'];
            $object->reference = $at['sku'];
            $object->minimal_quantity = 1;
            $object->weight = $weight;
            $object->price = (float)0.00;
            $object->wholesale_price = (float)0.00;
            $object->unit_price_impact = (float)0.00;
            if($i == 1)
            {
                $object->default_on = (int)1;
                $i = 2;
            }
            
            $object->save();
            
            $attr_id = $object->id;
            $id_product_attribute = $at['attribute_id'];
            $id_attributes[$attr_id] = $attr_id;
            
            $combinations[$attr_id][] = $id_product_attribute;
            
             $quantity[$attr_id] = $at['qty'];
        }
        $prd->addAttributeCombinationMultiple($id_attributes, $combinations);
        //echo "<pre>";print_r($quantity);echo "</pre>";die();
        foreach($quantity as $prd_attr=>$qty)
        {
            StockAvailable::updateQuantity($id,$prd_attr,$qty,$id_shop = Shop::getContextShopID(true));
        }
            //exit(); 
    }
    
    public function startCronUpdate()
    {
        ini_set('max_execution_time', -1);
        echo "working on the cron productify<br />";
        $cron_task = Db::getInstance()->executeS("Select * from "._DB_PREFIX_."productify_import where status = 1
                                                                     order by import_id asc, status desc 
                                                                     LIMIT 1 
                                                                     OFFSET 0");
        //echo count($cron_task)."<br />";
        if(count($cron_task) == 0)
        {
            echo "no cron processes to run";
            $output = shell_exec('crontab -l');
            $current_cron_array = explode("\n",$output);
            $current_url = _PS_BASE_URL_.__PS_BASE_URI__."module/productify/update";
            $new_cron = array();
            $update_cron = 0;
            foreach($current_cron_array as $cur_crn)
            {
                if(strpos($cur_crn,$current_url) != false)
                {
                    $update_cron = 1;
                    continue;
                }
                else
                {
                    $new_cron[] = $cur_crn;
                }
            }
            
            if($update_cron == 1)
            {
                $new_cron_text = implode("\n",$new_cron);
                file_put_contents('/tmp/crontab.txt', $new_cron_text.PHP_EOL);
                exec('crontab /tmp/crontab.txt');
            }
            
            Db::getInstance()->execute("delete from "._DB_PREFIX_."productify_import where where status = 0 && modified_time < (NOW() - INTERVAL 10 MINUTE)");
        }
        else
        {
            $task = $cron_task[0];
            set_time_limit(0);
            ini_set('memory_limit','555M');
            ini_set('upload_max_filesize','555M');
            
            if($task['images'] == 1)
            {
                $to_import_once = 2;
            }
            else
            {
                $to_import_once = 15;
            }
            if($task['processing'] == 1)
            {
                echo "another process running<br />";
                $process = Productify::checkDeadlockCondition();
                
                if($process != null)
                {
                    echo $process['pending_testing'];
                    $import_status = ($process['pending_testing'] == "remaining")
                                ?"The import process will continue but you might not get some products imported. So the total imported imported products might differ than the number that appears on the response email."
                                :"The import has been removed from the cron job list. Please retry the import process.<br /><br />";
                    $message = "<html>
                                <head>
                                    <title>Productify Import Notice</title>
                                </head>
                                <body>
                                    <p>
                                    Hi there,<br /><br />
                                    The import you started at ".$process['date_added']." has failed to continue due to some internal errors.<br /><br />
                                    Import Status: ".$process['imported']." (successful) and ".$process['failed']." (failed)<br /><br />".
                                    $import_status
                                    ."<br /><br />Thank you!
                                    </p>
                                </body>
                                </html>";
                    $to = $process['email'];
                    $subject = "Products import has failed";
                    
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                    $headers .= "From: Productify.com <noreply@productify.com> \r\n";
                    
                    $sendig_mail = mail($to,$subject,$message,$headers);  
                    //$id_lang = new Language((int)(Configuration::get('PS_LANG_DEFAULT')));
                    //Mail::Send($id_lang,$message,$subject,'',$to,'','noreply@productify.com','Productify.com');
                }
            }
            else
            {
                $imp_id = $task['import_id'];
                $url = $task['url'];
                $skus = $task['skus'];
                $import_images = $task['images'];
                $active_products = $task['enable_products'];
                $skus = Productify::change_std_toarray(json_decode($skus));
                
                
                $success = 0;
                $failure =0;
                
                $import_skus = array();
                $remaining_skus = array();
                $i = 0;
                foreach($skus as $s)
                {
                    $i++;
                    if($i<=$to_import_once)
                    {
                        $import_skus[] = $s; 
                    }
                    else
                    {
                        $remaining_skus[] = $s;
                    }
                }
                
                if(count($remaining_skus) == 0)
                {
                    $status = 0;
                    $rem_skus = "";
                }
                else
                {
                    $status = 1;
                    $rem_skus = json_encode($remaining_skus);
                }
                $skus = $import_skus;
                $processing = 1;
                Db::getInstance()->execute("update "._DB_PREFIX_."productify_import set 
                                                            status = $status, 
                                                            skus = '$rem_skus', 
                                                            processing = $processing 
                                                            where import_id = $imp_id");
                $xml = simplexml_load_file($url);
                foreach ($xml->products->product as $prd) {
                    //print_r($prd);
                    //echo "<br />";
    
                    $product = array(
                        "product_code" => "$prd->product_code",
                        "name" => "$prd->product_name",
                        "brand" => "$prd->brand",
                        "short_description" => "$prd->short_description",
                        "detail_description" => "$prd->detailed_description",
                        "active_prodcuts" => "$active_products",
                        "import_images" => "$import_images"
                    );
    
                    $categories = $prd->categories->category;
                    $product['category'] = "$categories";
                    $images = array();
                    foreach ($prd->media->image_url as $img) {
                        //print_r($img);
                        $image_default = "false";
                        foreach ($img->attributes() as $a => $b) {
                            $image_default = "$b";
                        }
                        $images[] = array("default" => "$image_default", "image_url" => "$img");
                    }
                    $product['media'] = $images;
                    $upload = (!isset($upload) || $upload != 1)?0:1;
                    
                    $sku = array();
                    $i=0;
                    foreach ($prd->skus->sku as $s) {
                        //print_r($s);
                        $variants = array();
                        $prd_sku = "$s->id";
                        
                        if (in_array($prd_sku, $skus)) {
                            $upload = 1;
                            $sku[$i]["id"] = "$prd_sku";
                            $sku[$i]["sale_price"] = "$s->sale_price";
                            $sku[$i]["retail_price"] = "$s->retail_price";
                            $sku[$i]["stock"] = "$s->stock";
                            $sku[$i]["ean"] = "$s->ean";
                            $sku[$i]["upc"] = "$s->upc";
                            $sku[$i]["weight"] = "$s->weight";
                            foreach ($s->variants->variant as $var) {
                                foreach ($var->attributes() as $a => $v) {
                                    $variants[] = array(
                                        'label' => "$v",
                                        'value' => "$var",
                                        'stock' => "$s->stock",
                                        'ean' => "$s->ean",
                                        'upc'=> "$s->upc",
                                        'weight' => "$s->weight",
                                        'sku' => "$prd_sku"
                                    );
                                }
                            }
                                
                                $sku[$i]['variants'][] = $variants;
                                
    
                                // Krita to authenticate only original skus
                                if (($key = array_search($prd_sku, $skus)) !== false) {
                                    unset($skus[$key]);
                                }
                        }
                    }
                    if($upload == 1)
                    {
                            $product['sku'] = $sku;
                            //add the product to the database
                            echo "check if product exists<br />";
                            Productify::check_product_exists($product,$imp_id);
                            echo "import product<br />";
                            $imported = Productify::importProducts($product,$imp_id);
                            $upload = 0;
                            
                            //Krita Please add this in respective place or it'll hunt you :)
                            if (Configuration::get('PS_SEARCH_INDEXATION'))
                            Search::indexation(true);
                            
                            
                            if($imported == 1)
                            {
                                $success++;
                            }
                            else
                            {
                                $failure++;
                            }
                            
                                $cron_status = Db::getInstance()->executeS("select imported, failed from "._DB_PREFIX_."productify_import where import_id = $imp_id");
                                $det = $cron_status[0];
                
                                $old_imported = $det['imported'];
                                $old_failed = $det['failed'];
                                $new_imported = (int)$old_imported + (int)$success;
                                $new_failed = (int)$old_failed + (int)$failure;
                        }
                                
                    }
                    //Db::getInstance()->execute("update "._DB_PREFIX_."productify_import set imported = $new_imported, failed = $new_failed, processing = 0 where import_id = $imp_id");
                    if($status == 0)
                    {
                        return $imp_id;
                    }
                    else
                    {
                        return null;
                    }
            }
        }
    }
    
    public function check_product_exists($product,$imp_id = 0)
    {
        $sku = $product['sku'][0]['id'];
        $name = $product['name'];
        $matching_products = 0;
        $matching_products = Product::searchByName((int)(Configuration::get('PS_LANG_DEFAULT')),$name);
        if(count($matching_products[0])>0)
        {
            if($matching_products[0]['name'] == $product['name'])
            {
                $prd_id = $matching_products[0]['id_product'];
                $product = new product((int)$prd_id);
                if($product->delete() && $imp_id != 0)
                {
                    
                    $updates = Db::getInstance()->getRow("select * from "._DB_PREFIX_."productify_import where import_id = $imp_id");
                    if($updates['updated_products'] == "")
                    {
                        $previous_updates = $name;
                    }
                    else
                    {
                        $previous_updates = $updates['updated_products'];
                        $previous_updates .= ", ".$name;
                    }
                    $upd = $previous_updates;
                    Db::getInstance()->execute("update "._DB_PREFIX_."productify_import set updated_products = '$upd' where import_id = $imp_id");
                }
                if($imp_id == 0)
                {
                    return 1;
                }                                        
                    
            }
            else
            {
                //echo "not deleted<br />";
                return 0;
            }
        }
        else
        {
            //echo "no match found<br />";
            return 0;
        }
        
    }
    
    function update_database($imp_id)
    {
        $new_imported = 0;
        $new_failed = 0;
        $cron_status = Db::getInstance()->executeS("select imported, failed from "._DB_PREFIX_."productify_import where import_id = $imp_id");
        $det = $cron_status[0];

        $old_imported = $det['imported'];
        $old_failed = $det['failed'];
        $new_imported = (int)$old_imported + 1;
        //$new_failed = (int)$old_failed + (int)$failure;
        Db::getInstance()->execute("update "._DB_PREFIX_."productify_import set imported = $new_imported, failed = $new_failed, processing = 0 where import_id = $imp_id");
    }
    
    function change_std_toarray($array)
    {
        if (is_array($array))
        {
            foreach ($array as $key => $value)
            {
                if (is_array($value))
                {
                    $array[$key] = Productify::change_std_toarray($value);
                }
                if ($value instanceof stdClass)
                {
                    $array[$key] = Productify::change_std_toarray((array)$value);
                }
            }
        }
        if ($array instanceof stdClass)
        {
            return Productify::change_std_toarray((array)$array);
        }
        return $array;
    }
    
    public function checkDeadlockCondition()
    {
        $conditions = Db::getInstance()->executeS("select * from "._DB_PREFIX_."productify_import where `modified_time` < (NOW() - INTERVAL 10 MINUTE) && Processing = 1 && status = 1");
        if(count($conditions[0]) > 0)
        {
            $process = $conditions[0];
            //echo "<pre>";print_r($process);echo "</pre>";
            if($process['skus'] != "")
            {
                $query = "update "._DB_PREFIX_."productify_import set processing = 0 where import_id = ".$process['import_id'];
                Db::getInstance()->execute($query);
                $process['pending_testing'] = "remaining";
            }
            else
            {
                Db::getInstance()->execute("update "._DB_PREFIX_."productify_import set processing = 0 where import_id = ".$process['import_id']);
                $process['pending_testing'] = "completed";
            }
            return $process;
        }
        else
        {
            return null;
        }
    }
        

}