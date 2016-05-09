<div class="container">
  <form class="form-signin">
    <h2>Statistics</h2>
    
    <label for="startdate" class="sr-only">Date From</label>
    <input type="text" id="startdate" class="form-control" placeholder="Date From" required>
    
    <label for="enddate" class="sr-only">Date To</label>
    <input type="text" id="enddate" class="form-control" placeholder="Date To" required>
    
    <label for="starttime" class="sr-only">Time From</label>
    <input type="text" id="starttime" class="form-control" placeholder="Time From" required>
    
    <label for="endtime" class="sr-only">Time To</label>
    <input type="text" id="endtime" class="form-control" placeholder="Time To" required>

    <a class="btn btn-lg btn-primary btn-block" data-type="getstatistics" id="mainaction">
        View Statistics
    </a>
  </form>
</div>