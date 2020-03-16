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
   <p>Hi, Seems like you have an account that needs to be live. <br> </p>
   <p>Google Account ID: <b>{{$accountData['g_acc_id']}} </b></p>
   <p>Account Name: <b>{{$accountData['acc_name']}} </b></p>
   <p>Current Stage: <b>{{$accountData['atStage']}} </b></p>

   <p>Thank you</p>
   <p>Automated Alert</p>
</div>