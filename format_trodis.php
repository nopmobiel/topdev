<?php
@ini_set('display_errors', 0);
@error_reporting(0);

/**
 * File: trodis2tdas.php
 * 
 * This file contains functions for encoding and decoding patient data,
 * as well as a function to convert Trodis format files to TDAS format.
 *
 * Functions:
 * - patenc($s): Encodes a string by shifting each character's ASCII value by 2.
 * - patdec($s): Decodes a string encoded by patenc() by shifting back ASCII values.
 * - trodis2tdas($filenaam): Converts a Trodis format file to TDAS format.
 *
 * The trodis2tdas() function reads a tab-delimited input file, processes each line,
 * and writes the converted data to a new file with a ".conv" extension.
 *
 * Note: This file requires PHP 5.0 or later.
 *
 * @author Unknown
 * @version 1.0
 * @date Unknown
 */

// Additional functions or code may be added here as needed.


function patenc($s)
{
    for( $i = 0; $i < strlen($s); $i++ )
        $r[] = ord($s[$i]) + 2;
    return implode('', $r);
}
 
function patdec($s)
{
    $s = explode(".", $s);
    for( $i = 0; $i < count($s); $i++ )
        $s[$i] = chr($s[$i] - 2);
    return implode('', $s);
}
 




// Format the Trodis file





function format_trodis($filenaam) 
{


	$targetfile=  $filenaam .".conv";
	$handle = fopen ($filenaam, "r");  	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
	$writehandle =fopen ($targetfile, "w");                           	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
  	while ($leeslijn=fgets($handle))   	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
  {                                   	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
  	$elementen = explode(chr(9),$leeslijn); 	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
 $begindatum=$elementen[0]; 	$line=    $begindatum . ";";   	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
 $anticoagulans=$elementen[1];  	$line.=    $anticoagulans	. ";";   	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
 $herc_datum=$elementen[2];  	$line.= $herc_datum       . ";";   	            	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
 $w1_zo=$elementen[24];		$line.= $w1_zo       . ";";   
 $w1_ma=$elementen[25];		$line.= $w1_ma       . ";";   	
 $w1_di=$elementen[26];		$line.= $w1_di       . ";";   
 $w1_wo=$elementen[27];		$line.= $w1_wo       . ";";   
 $w1_do=$elementen[28];		$line.= $w1_do       . ";";   			
 $w1_vr=$elementen[29];		$line.= $w1_vr       . ";";   
 $w1_za=$elementen[30];		  $line.= $w1_za        . ";";   
 $w2_zo=$elementen[31];		  $line.= $w2_zo        . ";";   
 $w2_ma=$elementen[32];		  $line.= $w2_ma        . ";";   
 $w2_di=$elementen[33];		  $line.= $w2_di        . ";";   
 $w2_wo=$elementen[34];		  $line.= $w2_wo        . ";";   
 $w2_do=$elementen[35];		  $line.= $w2_do        . ";";   
 $w2_vr=$elementen[36];		  $line.= $w2_vr   . ";";   
 $w2_za=$elementen[37];		  $line.= $w2_za   . ";";                                  
 $w3_zo=$elementen[38];		  $line.= $w3_zo   . ";";    
 $w3_ma=$elementen[39];		  $line.= $w3_ma   . ";";    
 $w3_di=$elementen[40];		  $line.= $w3_di   . ";";    
 $w3_wo=$elementen[41];		  $line.= $w3_wo   . ";";    
 $w3_do=$elementen[42];		  $line.= $w3_do    . ";";   
 $w3_vr=$elementen[43];		  $line.= $w3_vr    . ";";   
 $w3_za=$elementen[44];		  $line.= $w3_za    . ";";   
 $w4_zo=$elementen[45];		  $line.= $w4_zo    . ";";   
 $w4_ma=$elementen[46];		  $line.= $w4_ma    . ";";   
 $w4_di=$elementen[47];		  $line.= $w4_di    . ";";   
 $w4_wo=$elementen[48];		  $line.= $w4_wo    . ";";   
 $w4_do=$elementen[49];		  $line.= $w4_do    . ";";   
 $w4_vr=$elementen[50];		  $line.= $w4_vr    . ";";   
 $w4_za=$elementen[51];		  $line.= $w4_za    . ";";    	             	          	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	     	       	    	      	        	      	      	      	   	  	      	    	     	            	       	
 $w5_zo=$elementen[52];		  $line.= $w5_zo    . ";";   
 $w5_ma=$elementen[53];		  $line.= $w5_ma    . ";";   
 $w5_di=$elementen[54];		  $line.= $w5_di    . ";";   
 $w5_wo=$elementen[55];		  $line.= $w5_wo    . ";";   
 $w5_do=$elementen[56];		  $line.= $w5_do    . ";";   
 $w5_vr=$elementen[57];		  $line.= $w5_vr    . ";";   
 $w5_za=$elementen[58];		  $line.= $w5_za    . ";";   
 $w6_zo=$elementen[59];		  $line.= $w6_zo    . ";";   
 $w6_ma=$elementen[60];		  $line.= $w6_ma      . ";"; 
 $w6_di=$elementen[61];		  $line.= $w6_di   . ";";   
 $w6_wo=$elementen[62];		  $line.= $w6_wo   . ";";   
 $w6_do=$elementen[63];		  $line.= $w6_do   . ";";   
 $w6_vr=$elementen[64];		  $line.= $w6_vr   . ";";   
 $w6_za=$elementen[65];		  $line.= $w6_za   . ";";   
 $w7_zo=$elementen[66];		  $line.= $w7_zo   . ";";   
 $w7_ma=$elementen[67];		  $line.= $w7_ma   . ";";   
 $w7_di=$elementen[68];		  $line.= $w7_di   . ";";   
 $w7_wo=$elementen[69];		  $line.= $w7_wo   . ";";   
 $w7_do=$elementen[70];		  $line.= $w7_do   . ";";   
 $w7_vr=$elementen[71];		  $line.= $w7_vr   . ";";   
 $w7_za=$elementen[72];		  $line.= $w7_za   . ";";   
 $w8_zo=$elementen[73];		  $line.= $w8_zo   . ";";     
 $w8_ma=$elementen[74];		  $line.= $w8_ma   . ";";     
 $w8_di=$elementen[75];		  $line.= $w8_di   . ";";     
 $w8_wo=$elementen[76];		  $line.= $w8_wo   . ";";     
 $w8_do=$elementen[77];		  $line.= $w8_do   . ";";     
 $w8_vr=$elementen[78];		  $line.= $w8_vr   . ";";     
 $w8_za=$elementen[79];		  $line.= $w8_za       . ";";
 $aanroep=$elementen[4];		  $line.= $aanroep      . ";";     
 $naam=$elementen[5];		$line.= $naam         . ";";     
 $straat=$elementen[6];		  $line.= $straat       . ";";           
 $postcode=$elementen[7];	  $line.= $postcode     . ";";     
 $plaats=$elementen[8];		  $line.= $plaats       . ";";     
 $pat_nr=$elementen[9];		  $line.= $pat_nr       . ";";     
 $wijknr=$elementen[10];		  $line.= $wijknr       . ";";     
 $inr=$elementen[11];		$line.= $inr          . ";";     
 $werknr=$elementen[12];		  $line.= $werknr       . ";";     
 $code=$elementen[22];			$line.= $code. ";";
 // Nieuwe entries                        
 $inrbereik=$elementen[21];	 	  $line.=$inrbereik. ";";
 $vrijc1=$elementen[13];  	  $line.=str_replace (";", " ",$vrijc1) . ";"; // ingrijpen omdat er het puntkomma teken in voorkomt!
 $vrijc2=$elementen[14];		  $line.=str_replace (";", " ",$vrijc2 ). ";";
 $vrijc3=$elementen[15];		  $line.=str_replace (";", " ",$vrijc3 ). ";";
 $vrijc4=$elementen[16];		  $line.=str_replace (";", " ",$vrijc4 ). ";";
 $vrijp1=$elementen[17];		  $line.=str_replace (";", " ",$vrijp1 ). ";";
 $vrijp2=$elementen[18];		  $line.=str_replace (";", " ",$vrijp2 ). ";";
 $vrijp3=$elementen[19];		  $line.=str_replace (";", " ",$vrijp3 ). ";";
 $vrijp4=$elementen[20];		  $line.=str_replace (";", " ",$vrijp4 ). ";";


 $geboortedatum=$elementen[3];	 $line.=$geboortedatum. ";";     
 $startdag=$elementen[23];	  $line.=$startdag. ";";     
 $vakantietaal=$elementen[80];	  $line.=$vakantietaal. ";";     
 $hi_vakantietaal=$elementen[81];  $line.=$hi_vakantietaal. ";";     
 $coumarine=$elementen[82];			 $line.=$coumarine. ";";     
 $vorige_controle=$elementen[83];		 $line.=$vorige_controle. ";";     
 $voorvorige_controle=$elementen[84];		   $line.=$voorvorige_controle. ";";     
 $voor_voorvorige_controle=$elementen[85];	    $line.=$voor_voorvorige_controle. ";";     
 $dienstnummer=$elementen[86];		         	    $line.=$dienstnummer ;     
 fwrite ($writehandle,$line );
	  }	             	  
          
fclose ($handle);  
fclose ($writehandle);         
rename ($targetfile, $filenaam);

}
?>
