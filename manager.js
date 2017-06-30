// jQuery code
$(document).ready(function() {
  var msg = $("#msg").text("Loading courses into Select...");
  $.ajax( {
    type: "POST",
    url: 'ajax/manager.php',
    data: 'do=getSelect'
  })
  // Code to run if the request succeeds (is done);
  // The response is passed to the function
  .done(function(data) {
    $('#courseSelect').html(data);
  })
  // Code to run if the request fails; the raw request and
  // status codes are passed to the function
  .fail(function(xhr, status, errorThrown) {
    $("#msg").text("Problem with request. See console log for more data...");
    console.log("Error: " + errorThrown);
    console.log("Status: " + status);
    console.dir(xhr);
  })
  // Code to run regardless of success or failure;
  .always(function(xhr, status) {
    var msg = $("#msg").text();
    $("#msg").text(msg+"Ready!");
  });
});
function assignments(el) {
  var cid = el.value;
  if (cid == "none") {
    $("#msg").text('No course selected.');
    $("#ctrlPanel").text('');
    return;
  }
  $("#msg").text('Getting assignments from course '+cid+'...\n');
  $.ajax( {
    type: "POST",
    url: 'ajax/manager.php',
    data: 'do=getAsn&cid='+cid
  })
  // Code to run if the request succeeds (is done);
  // The response is passed to the function
  .done(function(data) {
    $('#ctrlPanel').html(data);
  })
  // Code to run if the request fails; the raw request and
  // status codes are passed to the function
  .fail(function(xhr, status, errorThrown) {
    $("#msg").text("Problem with request. See console log (F12) for more data...");
    console.log("Error: " + errorThrown);
    console.log("Status: " + status);
    console.dir(xhr);
  })
  // Code to run regardless of success or failure;
  .always(function(xhr, status) {
    var msg = $("#msg").text();
    $("#msg").html("<pre>"+msg+"Request complete!</pre>");
  });
}
function download(cid, asnid, all) {
  folder = $("#iofolder").val();
  console.log(cid, asnid, folder, all);
  $("#msg").text('Downloading files from course '+cid+' and assignment '+asnid+' to folder '+folder+'...\n');
  $.ajax( {
    type: "POST",
    url: 'ajax/canvasdownload.php',
    data: 'do=download&cid='+cid+'&asnid='+asnid+'&folder='+folder+'&all='+all
  })
  // Code to run if the request succeeds (is done);
  // The response is passed to the function
  .done(function(data) {
    $('#msg').html(data);
  })
  // Code to run if the request fails; the raw request and
  // status codes are passed to the function
  .fail(function(xhr, status, errorThrown) {
    $("#msg").text("Problem with request. See console log for more data...");
    console.log("Error: " + errorThrown);
    console.log("Status: " + status);
    console.dir(xhr);
  })
  // Code to run regardless of success or failure;
  .always(function(xhr, status) {
    var msg = $("#msg").text();
    $("#msg").html("<pre>"+msg+"Request complete!</pre>");
  });
}
function upload(cid, asnid, type) {
  var folder = $("#iofolder").val();
  var log = "grade.log";
  $("#msg").text('Uploading grade and feedback for course '+cid+' and assignment '+asnid+' from folder '+folder+'...\n');
  $.ajax( {
    type: "POST",
    url: 'ajax/canvasputgrade.php',
    data: 'do=upload&cid='+cid+'&asnid='+asnid+'&folder='+folder+'&log='+log+'&type='+type
  })
  // Code to run if the request succeeds (is done);
  // The response is passed to the function
  .done(function(data) {
    $('#msg').html(data);
  })
  // Code to run if the request fails; the raw request and
  // status codes are passed to the function
  .fail(function(xhr, status, errorThrown) {
    var msg = $("#msg").text();
    $("#msg").text(msg+"Problem with request. See console log for more data...");
    console.log("Error: " + errorThrown);
    console.log("Status: " + status);
    console.dir(xhr);
  })
  // Code to run regardless of success or failure;
  .always(function(xhr, status) {
    var msg = $("#msg").text();
    $("#msg").html("<pre>"+msg+"\nRequest complete.</pre>");
  });
}
function runscript(el) {
  var filePath = $("#scriptfolder").val();
  if (filePath.slice(-1) != "/" || filePath.slice(-1) != "\\") {
    filePath +=  "/";
  }
  filePath += el.value;
  $('#msg').text('Executing script: '+filePath);
  $.ajax( {
    type: "POST",
    url: 'ajax/manager.php',
    data: 'do=exec&path='+filePath
  })
  // Code to run if the request succeeds (is done);
  // The response data is passed to the function
  .done(function(data) {
    $('#msg').html(data);
  })
  // Code to run if the request fails; the raw request and
  // status codes are passed to the function
  .fail(function(xhr, status, errorThrown) {
    $("#msg").text("Problem with request. See console log for more data...");
    console.log("Error: " + errorThrown);
    console.log("Status: " + status);
    console.dir(xhr);
  })
  // Code to run regardless of success or failure;
  .always(function(xhr, status) {
    var msg = $("#msg").text();
    $("#msg").html("<pre>"+msg+"Request complete!</pre>");
  });
}
