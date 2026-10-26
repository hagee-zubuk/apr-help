<?php
if (!is_int($logtime)) {
    $logtime = 0;
    $logout = '';
} else {
    $logout = '<div style="display: inline-block; font-size: 70%;">(<a href="#" class="out">logout</a>)</div>'. PHP_EOL;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MIP REST Functions</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/1.0.3/css/bulma.min.css" integrity="sha512-4EnjWdm80dyWrJ7rh/tlhNt6fJL52dSDSHNEqfdVmBLpJLPrRYnFa+Kn4ZZL+FRkDL5/7lAXuHylzJkpzkSM2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
    <div class="content">
        <h1 class="title">MIP REST Refresh</h1>
        <div class="columns">
            <div class="column is-4">
    <p style="font-size: 80%;">
        Last apparent login: <?= date('m/d/y H:i e', $logtime) ?> <br />
        URL: <?= $resturl ?><br />
        Token: <?= $acctokn ?>
    </p>
    <!-- p>MIP Base URL: <b><?= $resturl?></b> </p -->

    <table id="tblButtons">
        <tr><td></td><td>
    <!-- button class="button opn is-danger is-light" id="btnLI" name="btnLI" data-label="Login">Re-Login</button -->
        </td></tr>
        <tr><td colspan="2"><p>Refresh:</p></td></tr>
        <tr><td>
    <button class="button glo is-danger" id="btn2" data-value="getVendors" data-label="Re-Fetch Vendor List" name="btn2">Vendors</button>
        </td><td>
    <button class="button glo is-danger" id="btn1" data-value="getChOA" data-label="Re-Fetch All GL Tables"
            name="btn1" >ALL AcctCode(GL) Tables</button>
        </td></tr>
        <tr><td></td><td>
    <button class="button glo is-warning" id="btn3" data-value="getCombi" data-label="Re-Fetch Combination Table"
            name="btn3">Acceptable-Combinations Table</button>
        </td></tr>

        <tr><td>
    <button class="button tac is-info" id="btnA" name="btnA" data-value="0"
            data-label="GL Codes Fetch">GL Codes</button>
        </td><td>
    <button class="button tac is-info" id="btnB" name="btnB" data-value="1"
            data-label="Entity Fetch">Entities</button>
        </td></tr>
        <tr><td>
    <button class="button tac is-info" id="btnC" name="btnC" data-value="2"
            data-label="Region Fetch">Regions</button>
        </td><td>
    <button class="button tac is-info" id="btnD" name="btnD" data-value="3"
            data-label="Payer Source Fetch">Payer Sources</button>
        </td></tr>
        <tr><td>
    <button class="button tac is-info" id="btnE" name="btnE" data-value="4"
            data-label="Services Fetch">Services</button>
        </td><td>
    <button class="button tac is-info" id="btnF" name="btnF" data-value="5"
            data-label="Departments Fetch">Departments</button>
        </td></tr>
        <tr><td>
    <button class="button tac is-info" id="btnG" name="btnG" data-value="7"
            data-label="Fiscal Year Fetch">FYs</button>
        </td><td>
    <button class="button tac is-info" id="btnH" name="btnH" data-value="9"
            data-label="Person Code Fetch">Person Codes</button>
        </td></tr>
        <tr><td></td><td>
    <button class="button tac is-info" id="btnH" name="btnH" data-value="10"
            data-label="Sites Fetch">Sites</button>
        </td></tr>
    </table>
<div id="wait" style="text-align: center;">    
    <img src="<?= base_url('images/wait.gif') ?>" alt="wait"/>
    <p>...please wait. this takes awhile...</p>
</div>
<div style="width: 100%; text-align: center;">
    <?= $logout?>
</div>
<div style="font-size:80%;">
    <p>Current counts</p>
    <div>
<pre>
    <?=var_dump($counts)?>
</pre>        
    </div>
</div>
        </div><!-- column -->
        <div class="column">
            <div id="debugsection" style="margin-top: 5%;">
                <input class="input" id="txtOpn" value="" readonly="readonly" />
                <textarea class="textarea" rows="24" id="txtDebug"></textarea>
            </div>
        </div>
    </div><!-- columns -->
    </div><!-- content -->
</body>
</html>
<script>

function doRest(zzcall) {
    var guid = $('#guid').val();
    $('#txtDebug').text('');
    var ajx = $.ajax({
                url: '<?=base_url('Rest')?>/' + zzcall
                , type: 'GET'
                , dataType: "text"
                , success: function(data) {
                        $('#txtDebug').text(data);
                        $('#tblButtons').show();
                        $('#wait').hide();
                        var psconsole = $('#txtDebug');
                        if (psconsole.length) psconsole.scrollTop(psconsole[0].scrollHeight - psconsole.height());
                    }
                , error: function(e) {
                        $('#txtDebug').text('something went wrong!');
                        $('#tblButtons').show();
                        $('#wait').hide();
                    }
        });                

}

$(document).ready(function() {
        $('.opn').on('click', function(e) {
                e.preventDefault();
                var lbl = $(this).data('label');
                $('#txtOpn').val(lbl);
                $('#tblButtons').hide();
                $('#wait').show();
                doRest('login');
            });
        $('.out').on('click', function(e) {
                e.preventDefault();
                var lbl = "Logout";
                $('#txtOpn').val(lbl);
                $('#tblButtons').hide();
                $('#wait').show();
                doRest('logout');
            });
        $('.glo').on('click', function(e) {
                e.preventDefault();
                var lbl = $(this).data('label');
                var opn = $(this).data('value');
                $('#txtOpn').val(lbl);
                $('#tblButtons').hide();
                $('#wait').show();
                doRest(opn);
            });
        $('.tac').on('click', function(e) {
                e.preventDefault();
                var lbl = $(this).data('label');
                var opn = 'getTAC/' + $(this).data('value');
                $('#txtOpn').val(lbl);
                $('#tblButtons').hide();
                $('#wait').show();
                doRest(opn);
            });
        $('#wait').hide();
    });
</script>