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
   <p>Hi, <br> you have a comment on: </p>
   <p>Google Account ID: <b>{{$commentData['g_acc_id']}} </b></p>
   <p>Account Name: <b>{{$commentData['acc_name']}} </b></p>
   <p>Current Stage: <b>{{$commentData['atStage']}} </b></p>
   <p>Comment By : <b>{{$commentData['comment_by']}}  ({{$commentData['comment_by_email']}}) </b></p>
   <p>Commented on : <b>{{$commentData['comment_at']}} </b></p>
   <p class="comment">Comment : <br> <b>{{$commentData['comment']}} </b></p>

   <p>Thank you</p>
   <p>Automated Comment Alert</p>
</div>