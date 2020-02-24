<style>
	.main{
    	padding:10px;
    }
    .issue-card{
    	margin: 10px;
      margin-left:15px;
      border-bottom: 1px dashed grey;
    }
    .title{
    	color: red;
    }
</style>
<div class="main">
   <p>Hi, <br> Reported issue(s) has not been resolved yet, please have a look into this : </p>
   <p>Account ID: {{$alertdata['g_id']}}</p>
   @foreach ($alertdata['alerts'] as $alert)
      <div class="issue-card">
         <p class="title"><b>Issue :</b> {{ $alert['title'] }}</p>
         <p><b>New Value :</b> {{ convertToFloat($alert['new_value']) }}</p>
         <p><b>Old Value :</b> {{ convertToFloat($alert['old_value']) }}</p>
         <p><b>Diff :</b> {{ convertToFloat($alert['difference']) }}</p>
      </div>
   @endforeach
   <p>Thank you</p>
   <p>Automated Alerts</p>
</div>