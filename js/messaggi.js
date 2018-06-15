function conferma(msg)
   {
 	//var msg1 = decodeURIComponent((msg).replace( /\+/g, ' '));

   var response = confirm(msg); 
   return(response);
   }
   
function avviso_no(msg)
   {
   alert(msg);
   }
   
function avviso(msg, retPage)
   {
   alert(msg);
   window.location=retPage;
   }
   
   
function avviso(msg, retPage, targetframe)
   {
   alert(msg);
   window.location=retPage;
   window.target=targetframe;
   }
/*-------------------------------------
		When ESC pressed go to blank page
---------------------------------------*/
document.onkeydown = function(evt) {
    evt = evt || window.event;
    if (evt.keyCode == 27) {
       window.location.href = "../php/frame_set.php";
    }
};
