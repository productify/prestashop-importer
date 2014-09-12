<style>

    .hidd
    {
        opacity: 0;
    }

</style>

<span id="first-msg">{$message}</span>
{$message = null}

<div id="carrier_wizard">
    <ul class="steps nbr_steps_4 anchor">
        <li class="done">
            <a href="" class="done" isdone="1" rel="1">
                <span class="stepNumber">1</span>
                            <span class="stepDesc">
                                Enter URL<br>
                                                        </span>
                <span class="chevron"></span>
            </a>
        </li>
        <li class="selected" id="take2">
            <a href="#" class="selected" isdone="1" rel="2" id="link2" onclick="reverse_options()">
                <span class="stepNumber">2</span>
                            <span class="stepDesc">
                                Select Products<br>
                                                        </span>
                <span class="chevron"></span>
            </a>
        </li>
        <li class="done" id="take3">
            <a href="#" class="done" isdone="1" rel="3" id="link3">
                <span class="stepNumber">3</span>
                            <span class="stepDesc">
                                More Options<br>
                                                        </span>
                <span class="chevron"></span>
            </a>
        </li>
        <li class="done">
            <a href="#" class="done" isdone="1" rel="4">
                <span class="stepNumber">4</span>
                            <span class="stepDesc">
                                Processing<br>
                                                        </span>
                <span class="chevron"></span>
            </a>
        </li>
    </ul>
</div>
<br/>
<br/>
<br/>

<fieldset>
<div class="panel col-lg-12">

{if $MOD_PRODUCTIFY_URL}
    <div class="panel-heading">
        <i class="icon-AdminCatalog"></i> Product List
    </div>
    <div class="alert alert-info" id="second-msg">Total number of products: {$xml|@count}</div>

    <div id="step2">
    <form method="post" class="defaultForm  form-horizontal">
    <div id="sel_table">
        <div class="table-responsive clearfix">
            <table class="table table-striped linksmenutop" id="datatable">
                <thead>
                <tr class="nodrag nodrop">
                    <th class="">
                        <span class="title_box ">SN</span>
                    </th>
                    <th class="">
                                    <span class="title_box"><input type="checkbox" name="check-all" id="check-all"
                                                                   onchange="checkAll()"/><label for="check-all">&nbsp;Select
                                            All</label></span>
                    </th>
                    <th class="">
                        <span class="title_box ">Product Name</span>
                    </th>
                    <th class="">
                        <span class="title_box ">Brand</span>
                    </th>
                    <th class="">
                        <span class="title_box"></span>
                    </th>
                    <th class="">
                        <span class="title_box ">SKU</span>
                    </th>
                    <th class="">
                        <span class="title_box ">Varient</span>
                    </th>
                </tr>
                </thead>

                <tbody>

                {foreach $xml as $value}
                    <tr>
                        <td>{counter}</td>
                        <td><input name="" type="checkbox" value="{$value->skus->sku->id}"
                                   class="product" id="{$value@iteration}"/></td>
                        <td>{$value->product_name}</td>
                        <td>{$value->brand}</td>

                        <td>
                            {foreach $value->skus as $sku}
                                {$num_skus = $sku|@count}
                                {foreach $sku->sku as $sk}
                                    <input name="product[]"
                                           type="checkbox"
                                           class="product_sku product_sku_{$value@iteration}" 
                                           value="{$sk->id}"
                                           id="prd_sku_{$value@iteration}"/>
                                    <br/>
                                {/foreach}
                            {/foreach}
                        </td>

                        <td>
                            {foreach $value->skus as $sku}
                                {foreach $sku->sku as $sk}
                                    {$sk->id}
                                    <br/>
                                {/foreach}
                            {/foreach}
                        </td>

                        <td>
                            {foreach $value->skus as $sku}
                                {foreach $sku->sku as $sk}
                                    {foreach $sk->variants as $v}
                                        {$v->variant}
                                        <br/>
                                    {/foreach}
                                {/foreach}
                            {/foreach}
                        </td>

                    </tr>
                {/foreach}
                </tbody>
            </table>

        </div>

        <div class="panel-footer">
            <button type="button" id="next-btn" name="next_submit"
                    class="btn btn-default pull-right">
                <i class="process-icon-next"></i> Next
            </button>
        </div>
    </div>

    <div id="options" style="display: none">
        <div class="table-responsive clearfix">
            <!--<div class="alert alert-info">
                You've selected:<br/>
                <span class="prods"></span> Product(s) (with <span class="sku"></span> SKUs)
            </div>-->

            <div class="form-group">
                <label class="control-label col-lg-3 required" for="mail">
                    <span title="" data-html="true" data-toggle="tooltip" class="label-tooltip" data-original-title="The email address that would receive a mail notification upon completion of the process.">
                        Email address:
                    </span>
                </label>

                <div class="col-lg-9 ">
                    <input type="email" required="required" class="" name="mail" id="mail" />
                </div>
            </div>

            <h4>Other options</h4>

            <div class="form-group">

                <label class="control-label col-lg-3 " for="images">
                    Import product images?
                </label>


                <div class="col-lg-9 ">

                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" value="1" id="images_on" name="images"/>
                        <label for="images_on">
                            Yes
                        </label>

                        <input type="radio" checked="checked" value="0" id="images_off" name="images"/>
                        <label for="images_off">
                            No
                        </label>

                        <a class="slide-button btn"></a>

                    </span>

                    <p class="help-block">
                        Import images for all the selected products.
                    </p>

                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3 " for="active">
                    Make all imported products active?
                </label>


                <div class="col-lg-9 ">

                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" checked="checked" value="1" id="active_on" name="active"/>
                                    <label for="active_on">
                                        Yes
                                    </label>

                                    <input type="radio" value="0" id="active_off" name="active"/>
                                    <label for="active_off">
                                        No
                                    </label>

                                    <a class="slide-button btn"></a>

                                </span>


                    <p class="help-block">
                        Enable all the imported products.
                    </p>

                </div>

            </div>
        </div>
        <div class="panel-footer">

            <button type="button" name="submit_select" onclick="reverse_options()"
                    class="btn btn-default pull-left">
                <i class="process-icon-back"></i> Back
            </button>

            <button type="submit" value="1" id="submit_select" name="submit_select"
                    class="btn btn-default pull-right" onclick="show_div(event);">
                <i class="process-icon-next"></i> Continue
            </button>
        </div>
    </div>
    </form>
    </div>
    <div class="entry-edit table-responsive clearfix" id="step2" style="display: none">
        <h1>Processing</h1>

        The products are being processed.
        <!--It might take several minutes. Please don't close your browser window until you get to Success Page.<br/>
        Once done, you'll be automatically redirected to success page!-->
    </div>
{/if}

</div>
</fieldset>

<script type="text/javascript">

    //for toggling between divs
    function show_div(event) {

        if (document.getElementById("mail").value == '') {
            alert('Please enter a valid email address.');
            event.preventDefault();
        }

        else {
            document.getElementById('step2').style.display = 'block';
            document.getElementById('step1').style.display = 'none';
        }
    }

    function reverse_options() {
        document.getElementById('options').style.display = 'none';
        document.getElementById('sel_table').style.display = 'block';
        document.getElementById("take3").className = "done";
        document.getElementById("link3").className = "done";
        document.getElementById("take2").className = "selected";
        document.getElementById("link2").className = "selected";
    }

    function show_options() {
        document.getElementById('options').style.display = 'block';
        document.getElementById('sel_table').style.display = 'none';
        document.getElementById("take3").className = "selected";
        document.getElementById("link3").className = "selected";
        document.getElementById("take2").className = "done";
        document.getElementById("link2").className = "done";
    }


    // for particular item selected only overcoming removal of nodes from the DOM
    $(document).on('click', '.product', function (e) {
        var id = $(this).attr('id');
        if (this.checked) {
            $('.product_sku_' + id).each(function () {
                this.checked = true;
            });
        } else {
            $('.product_sku_' + id).each(function () {
                this.checked = false;
            });
        }
    });

    $(document).on('click', '.product_sku', function (e) {
        var thestringid = $(this).attr('id');
        var this_class = $(this).attr('class').split(' ')[1]
        var id = thestringid.replace( /^\D+/g, '');
        if (this.checked) {
            $('#' + id).each(function () {
                this.checked = true;
            });
        } else {
            if($('input:checkbox.'+this_class+':checked').size() == 0)
            {
                $('#' + id).each(function () {
                    this.checked = false;
                });
            }
        }
    });

    $(document).on('click', '#next-btn', function (e) {

        document.getElementById('first-msg').style.display = 'none';
        document.getElementById('second-msg').style.display = 'none';
        var nHidden = $(otable.fnGetHiddenTrNodes()).find('input:checked');
        if (!$('.product:checked').length > 0 && nHidden.length == 0) {
            e.preventDefault();
            alert('Please select at least one product.');
        }
        else {
            var prods = $('.product:checked').length;
            var sku = $('.product_sku:checked').length;
            $('.sku').html(sku);
            $('.prods').html(prods);
            show_options();
        }
    });


    $(document).ready(function () {
        /*
         * Function: fnGetHiddenTrNodes
         * Purpose:  Get all of the hidden TR nodes (i.e. the ones which aren't on display)
         * Returns:  array:
         * Inputs:   object:oSettings - DataTables settings object
         */
        $.fn.dataTableExt.oApi.fnGetHiddenTrNodes = function ( oSettings )
        {
            /* Note the use of a DataTables 'private' function thought the 'oApi' object */
            var anNodes = this.oApi._fnGetTrNodes( oSettings );
            var anDisplay = $('tbody tr', oSettings.nTable);

            /* Remove nodes which are being displayed */
            for ( var i=0 ; i<anDisplay.length ; i++ )
            {
                var iIndex = jQuery.inArray( anDisplay[i], anNodes );
                if ( iIndex != -1 )
                {
                    anNodes.splice( iIndex, 1 );
                }
            }

            /* Fire back the array to the caller */
            return anNodes;
        }

        otable = $('#datatable').dataTable({
            "sDom": "<'row'<'col-xs-6'T><'col-xs-6'f>r>t<'row'<'col-xs-6'i><'col-xs-6'p>>",
            "sPaginationType": "bootstrap",
            "aLengthMenu": [
                [10, 20, 50],
                [10, 20, 50]
            ],
            // Disable sorting on the no-sort class
            "aoColumnDefs": [
                {
                    "bSortable": false,
                    "aTargets": [1, 4]
                }
            ]
        });

        /* Init the table and fire off a call to get the hidden nodes. */

        $('form').submit(function() {

            var nHidden = $(otable.fnGetHiddenTrNodes()).find('input:checked').appendTo(this).addClass('hidd');
            //alert( nHidden.length +' nodes were returned' );
        } );

        // checkall and uncheckall overcoming removal of nodes from the DOM
        $("#check-all").click(function () {
            var selected = new Array();
            if (this.checked) {
                $(otable.fnGetNodes()).find(':checkbox').each(function () {
                    $this = $(this);
                    $this.attr('checked', 'checked');
                    selected.push($this.val());
                });

            } else {
                $(otable.fnGetNodes()).find(':checkbox').each(function () {
                    $this = $(this);
                    $this.attr('checked', false);
                    selected.push($this.val());
                });
            }
            // convert to a string
            var mystring = selected.join();
            //alert(mystring);
        });

    });

</script>
