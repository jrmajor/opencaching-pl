
<script>
    function checkForm()
    {
        if (document.editmp3_form.title.value == "")
        {
            alert("{{editmp3_01}}");
            return false;
        }

        if (document.editmp3_form.file.value == "")
        {
            /*alert("Proszę podać źródło obrazka!");
             return false;*/
        }

        return true;
    }
</script>

<div class="content2-pagetitle"><img src="/images/blue/podcache-mp3.png" class="icon32" alt="" />&nbsp;<b>{mp3typedesc}: </b><a href="/viewcache.php?cacheid={cacheid}">{cachename}</a></div>

<form action="editmp3.php" method="post" enctype="multipart/form-data" name="editmp3_form" dir="ltr" onsubmit="return checkForm();">
    <input type="hidden" name="uuid" value="{uuid}" />
    <div class="searchdiv">
        <table class="table">
            <colgroup>
                <col width="100">
                <col>
            </colgroup>
            <tr class="form-group-sm">
                <td valign="top">{{editmp3_03}}:</td>
                <td><input class="form-control input200" name="title" type="text" value="{title}" size="43" /> {errnotitledesc}</td>
            </tr>

            <tr>
                <td valign="top">{{editmp3_04}}:</td>
                <td>
                    <div class="form-inline">
                    <?php $view->callChunk('fileUpload','file', 'audio/*', $view->maxmp3size); ?>
                    </div>
                </td>
            </tr>
            <tr><td class="spacer" colspan="2"></td></tr>
            {begin_cacheonly}
            <tr>
                <td align="right"><input class="checkbox" type="checkbox" name="notdisplay" value="1" {notdisplaychecked}></td>
                <td>{{editmp3_05}}</td>
            </tr>
            {end_cacheonly}

            <tr><td class="spacer" colspan="2"></td></tr>

            <tr>
                <td class="header-small" colspan="2">
                    <input type="reset" name="reset" value="{{reset}}" class="btn btn-default"/>&nbsp;&nbsp;
                    <input type="submit" name="submit" value="{{submit}}" class="btn btn-primary"/>
                </td>
            </tr>
        </table>
</form>
</div>
