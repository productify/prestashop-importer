<div id="carrier_wizard">
    <ul class="steps nbr_steps_4 anchor">
        <li class="selected">
            <a href="#step-1" class="selected" isdone="1" rel="1">
                <span class="stepNumber">1</span>
                            <span class="stepDesc">
                                Enter URL<br>
                                                        </span>
                <span class="chevron"></span>
            </a>
        </li>
        <li class="done">
            <a href="#" class="done" isdone="1" rel="2">
                <span class="stepNumber">2</span>
                            <span class="stepDesc">
                                Select Products<br>
                                                        </span>
                <span class="chevron"></span>
            </a>
        </li>
        <li class="done">
            <a href="#" class="done" isdone="1" rel="3">
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
{*smarty variable, output any success/error messages*}
{$message}

<fieldset>

    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-link"></i> XML Import
        </div>


        <form method="post" class="defaultForm  form-horizontal">
            <div class="form-group">
                <label for="MOD_PRODUCTIFY_URL" class="control-label col-lg-3">URL: </label>

                <div class="col-lg-9 ">
                    <input id="MOD_PRODUCTIFY_URL" name="MOD_PRODUCTIFY_URL" type="url" value=""/>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" value="1" id="submit_{$module_name}" name="submit_{$module_name}"
                        class="btn btn-default pull-right">
                    <i class="process-icon-next"></i> Next
                </button>
            </div>
        </form>

    </div>
</fieldset>


