//
//	Funzione per valorizzare i dati durante l'inserimento dei costi
//
// Input: source data
//           target
//           mandatory field (true, false)
//           
function setHiddenCostValues(f_source, f_target, f_required) {
	var retCode=false,
	      sourceField =  document.getElementById(f_source),
	      targetField =  document.getElementsByName(f_target),
	      i;
	      //alert('ecco');
	   //alert(targeField.length);
	      
	if(sourceField && targetField) { // OK, found!
	      //alert('ecco1');
	   //alert(targetField.length);
	    for(i=0; i < targetField.length; i++) {
	    	//alert(i);
	          targetField[i].value = sourceField.value;
         }

      	 if(!(sourceField.value.trim() == '') && f_required) { // Empty string
      	    retCode=true;
      }
      }
 //alert(targetField.value);
 return(retCode);
}
