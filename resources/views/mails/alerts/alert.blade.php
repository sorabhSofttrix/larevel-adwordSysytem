<style>
	.main{
    	padding:10px;
    }
    .issue-card{
    	margin: 10px;
        margin-left:15px;
    }
    .title{
    	color: red;
    }
</style>
<div class="main">
   <p>Hi, <br> it seems like there are some issues with : </p>
   <p>Account ID: {{$alertdata['g_id']}}</p>
@foreach ($alertdata['alerts'] as $alert)
<div class="issue-card">
   <p class="title"><b>Issue :</b> {{ $alert['title'] }}</p>
   <p><b>New Value :</b> {{ convertToFloat($alert['new_value']) }}</p>
   <p><b>Old Value :</b> {{ convertToFloat($alert['old_value']) }}</p>
   <p><b>Diff :</b> {{ convertToFloat($alert['difference']) }}</p>
</div>
@endforeach
</div>