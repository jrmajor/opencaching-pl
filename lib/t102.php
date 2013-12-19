<script type='text/javascript' src='https://www.google.com/jsapi'></script>
<script type='text/javascript' src="lib/js/GCT.js"></script>
<script type='text/javascript' src="lib/js/wz_tooltip.js"></script>

<div id='idGTC' ></div>

<?php
 echo "<script type='text/javascript'>

	var gct = new GCT( 'idGTC' );

	gct.addColumn('number', '".tr('ranking')."', 'width:60px; text-align: left;');
	gct.addColumn('number', '".tr('caches')."', 'width:60px; text-align: left;');
	gct.addColumn('string', '".tr('user')."' );
 		
 	gct.addColumn('string', 'UserName' );

 
 	//gct.addTblOption( 'pageSize', 100 );
 	//gct.addTblOption( 'showRowNumber', true );
 	//gct.addTblOption( 'sortColumn', 0 );
	
 		 	
 	
</script>";

 

require_once('./lib/db.php');

$sRok = "";
$sMc = "";
$sCondition = "";
$nIsCondition = 0;
$nMyRanking = 0;


if ( isset( $_REQUEST[ 'Rok' ]) )
	$sRok =  $_REQUEST[ 'Rok' ];

if ( isset( $_REQUEST[ 'Mc' ]) )
	$sMc =  $_REQUEST[ 'Mc' ];



if ( $sRok <> "" and $sMc <> "" )
{
	$sData_od = $sRok.'-'.$sMc.'-'.'01';
	
	$dDate = new DateTime( $sData_od );
	$dDate->add( new DateInterval('P1M') );
	$nIsCondition = 1;
}

if ( $sRok <> "" and $sMc == "" )
{
	$sData_od = $sRok.'-01-01';

	$dDate = new DateTime( $sData_od );
	$dDate->add( new DateInterval('P1Y') );
	$nIsCondition = 1;
}


if ( $nIsCondition )
{
	$sData_do = $dDate->format( 'Y-m-d');	
	$sCondition = "and date >='" .$sData_od ."' and date < '".$sData_do."'";	
}



$dbc = new dataBase();

$query = 
		"SELECT COUNT(*) count, u.username username, u.user_id user_id, 
		u.date_created date_created, u.description description
		
		FROM 
		cache_logs cl
		join caches c on c.cache_id = cl.cache_id
		join user u on cl.user_id = u.user_id
		
		WHERE cl.deleted=0 AND cl.type=1 "
		
		. $sCondition .

		
		//"GROUP BY u.user_id "; 
		
		
		"GROUP BY u.user_id   		
		ORDER BY count DESC, u.username ASC";

		
$dbc->multiVariableQuery($query);

echo "<script type='text/javascript'>";

$nRanking = 0;
$sOpis = "";
$nOldCount = -1;
$nPos = 0;
$nMyRanking = 0;
$nMyRealPos = 0;




while ( $record = $dbc->dbResultFetch() )
{	
	if ( $record[ "description" ] <> "" )
	{
		$sOpis = $record[ "description" ];
		
		$sOpis = str_replace("\r\n", " ",$sOpis);
		$sOpis = str_replace("\n", " ",$sOpis);
		$sOpis = str_replace("'", "-",$sOpis);
		$sOpis = str_replace("\"", " ",$sOpis);		
	}
	else
		$sOpis = "Niestety, brak opisu <img src=lib/tinymce/plugins/emotions/images/smiley-surprised.gif />";
	
	
	$sProfil = "<b>Zarejestrowany od:</b> ".$record[ "date_created" ]
		 ." <br><b>Opis: </b> ".$sOpis;

	$nCount = $record[ "count" ];
	$sUN = $record[ "username" ];
	$sUsername = '<a href="viewprofile.php?userid='.$record["user_id"].'" onmouseover="Tip(\\\''.$sProfil.'\\\')" onmouseout="UnTip()"  >'.$record[ "username" ].'</a>';

	
	if ( $nCount != $nOldCount )
	{				
		$nRanking++;
		$nOldCount = $nCount; 
	}
	
	$nPos++;
	
	echo "
			gct.addEmptyRow();
			gct.addToLastRow( 0, $nRanking );
			gct.addToLastRow( 1, $nCount );
			gct.addToLastRow( 2, '$sUsername' );
			gct.addToLastRow( 3, '$sUN' );
		";
	
	if ( $usr['userid'] == $record[ 'user_id'] )
	{
		$nMyRanking = $nRanking;
		$nMyRealPos = $nPos;
		echo " gct.addToLastRow( 3, '<span style=\"color:red\">$sUN</span>' );";
	} 

	 
	
	//gct.addEmptyRow();
	//gct.addToLastRow( 0, $nRanking );
	//gct.addToLastRow( 1, $nCount );
	//gct.addToLastRow( 2, '$sUsername' );
	
	//{v: 12500, f: '$12,500'}
}

//echo "alert( '$nMyRanking' ); ";

echo "gct.drawTable();";
//echo "document.filtrDat.Ranking.value = '".$nMyRanking."';";

//echo "document.filtrDat.Ranking.value = '".$nMyRanking." / ".$nRanking."';";
//echo "document.filtrDat.RealPosOfTable.value = '".$nMyRealPos."';";

echo "document.Position.Ranking.value = '".$nMyRanking." / ".$nRanking."';";
echo "document.Position.RealPosOfTable.value = '".$nMyRealPos."';";

//echo "gct.getFilteredRows( [{column: 3, minValue: 'Qba', maxValue: 'Qbaz'}] );";

//echo "gct.addTblOption( 'startPage', 5 );";
//echo "gct.drawTable();";

echo "</script>";




/* Ex aequo
 * 
 * $nRanking = 0;
$sOpis = "";
$sLUsername = "";
$nOldCount = -1;


while ( $record = $dbc->dbResultFetch() )
{	
	if ( $record[ "description" ] <> "" )
	{
		$sOpis = $record[ "description" ];
		
		$sOpis = str_replace("\r\n", " ",$sOpis);
		$sOpis = str_replace("\n", " ",$sOpis);
		$sOpis = str_replace("'", "-",$sOpis);
		$sOpis = str_replace("\"", " ",$sOpis);		
	}
	else
		$sOpis = "Niestety, brak opisu <img src=lib/tinymce/plugins/emotions/images/smiley-surprised.gif />";
	
	
	$sProfil = "<b>Zarejestrowany od:</b> ".$record[ "date_created" ]
		 ." <br><b>Opis: </b> ".$sOpis;

	$nCount = $record[ "count" ];
	$sUsername = '<a href="viewprofile.php?userid='.$record["user_id"].'" onmouseover="Tip(\\\''.$sProfil.'\\\')" onmouseout="UnTip()"  >'.$record[ "username" ].'</a>';
	
	if ($nOldCount == -1 )
		$nOldCount = $nCount;
	
	if ( $nCount != $nOldCount )
	{				
		$nRanking++;
		
		echo "
		gct.addEmptyRow();
		gct.addToLastRow( 0, $nRanking );
		gct.addToLastRow( 1, $nOldCount );
		gct.addToLastRow( 2, '$sLUsername' );
		";
		
		$sLUsername = $sUsername;				
		$nOldCount = $nCount; 
	}
	else
	{
		if ( $sLUsername <> "" )
			$sLUsername .= ", " ;
		
		$sLUsername .= $sUsername;
	} 
	
}

if ( $nOldCount != -1 )
{
	$nRanking++;
	
	echo "
	gct.addEmptyRow();
	gct.addToLastRow( 0, $nRanking );
	gct.addToLastRow( 1, $nOldCount );
	gct.addToLastRow( 2, '$sLUsername' );
	";
}
*/

?>
