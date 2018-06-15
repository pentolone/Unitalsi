window.onload = function()
   {
   	var valid=false;
 //  	alert("FATTO");
 	//var msg1 = decodeURIComponent((msg).replace( /\+/g, ' '));
 //	var el = document.querySelector('input[id="searchTxt"]');
   var el2 = document.querySelector('input[id="luogo_nascita"]');
   var el1 = document.querySelector('input[id="citta"]');

 	//alert(document.forms.length);

 	if(el1) {
 		//alert("Got it!");
 		valid=true;
 		//alert(el.value);
     }		
 	else {
 		//alert("NOT FOUND");
     }	
   
   if(!valid)
      return(false);
   // Ok, elemento trovato, aggiungiamo	il listener
  /* el.addEventListener('input', function(e) {
   	    var input = e.target,
             list = input.getAttribute('list'),
             options = document.querySelectorAll('#' + list + ' option'),
   	          hiddenInput = document.getElementById('id-hidden'),
   	          inputValue = input.value;

      for(var i = 0; i < options.length; i++) {
            var option = options[i];

            if(option.innerText == inputValue) {
               hiddenInput.value = option.getAttribute('data-value');
             //alert(hiddenInput.value);
            //alert(capInput.value);
            //alert(codiceCatastale.value);    
               document.forms["searchTxt"].submit();           
               break;
             }    
         }

   });
//document.querySelector('input[list]').addEventListener('input', function(e) {*/
    el1.addEventListener('input', function(e) {
    	// alert('el1');
    var input = e.target,
          list = input.getAttribute('list'),
          options = document.querySelectorAll('#' + list + ' option'),
          hiddenInput = document.getElementById(input.id + '-hidden'),
          array,
          capInput = document.getElementsByName('cap')[0],      
          idProvincia = document.getElementsByName('id_provincia')[0],      
          inputValue = input.value;
          
    hiddenInput.value = inputValue;

    for(var i = 0; i < options.length; i++) {
        var option = options[i];
        
        //alert(el1.length);

        if(option.innerText == inputValue) {
 //           alert(inputValue.value);
        	   array = option.getAttribute('data-value').split(';');
            hiddenInput.value = option.getAttribute('data-value');
            //hiddenInput.value = array[0];
            hiddenInput.value = inputValue;
            capInput.value = array[1];
            idProvincia.value = array[2];

            //alert(capInput.value);
            //alert(codiceCatastale.value);
            break;
        }
    }
});
    el2.addEventListener('input', function(e) {
    var input = e.target,
          list = input.getAttribute('list'),
          options = document.querySelectorAll('#' + list + ' option'),
          hiddenInput = document.getElementById(input.id + '-hidden'),
          array,
          codiceCatastale = document.getElementsByName('codice_catastale')[0],      
          inputValue = input.value;
          

    for(var i = 0; i < options.length; i++) {
        var option = options[i];
        
        if(option.innerText == inputValue) {
//            alert(inputValue.value);
        	   array = option.getAttribute('data-value').split(';');
            hiddenInput.value = option.getAttribute('data-value');
            hiddenInput.value = inputValue;
            //hiddenInput.value = array[0];
            hiddenInput.value = inputValue;
            codiceCatastale.value = array[1];

            //alert(capInput.value);
            break;
        } 
      else {    
            hiddenInput.value = input.value;
          }
    }
});
 }
 


function disableEnterKey(e)
{
     var key;      
     if(window.event)
          key = window.event.keyCode; //IE
     else
          key = e.which; //firefox      
     return (key != 13);
}

