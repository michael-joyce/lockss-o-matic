/**
 *  Lockss-o-matic UI code.
 *
 */
$(document).ready(function() {
    
    /*
    * Nav tabs default.
    * Include after jQuery and Bootstrap.
    */
    $('#defualttabsul a').click(function (e) {
      // e.preventDefault();
      $(this).tab('show');
      
    });

    var url = $(location).attr('href');
    if(/deposits/i.test(url))
    {
      //alert("URL contains the string 'deposits'");
      $('#depositsnavli').toggleClass('active');
    } else if (/contentproviders/i.test(url)) {
      $('#contentprovidersnavli').toggleClass('active');
    } else if (/contentowners/i.test(url)) {
      $('#contentownersnavli').toggleClass('active');
    } else if (/#reports/i.test(url)){
      $('#reportsnavli').toggleClass('active');
    } else if(/#default/i.test(url)) {
      // default to Dashboard tab.
      $('#dashboardnavli').toggleClass('active');
    } else {
      // Dashboard default view.
      $('#dashboardnavli').toggleClass('active');
    }
   
} );