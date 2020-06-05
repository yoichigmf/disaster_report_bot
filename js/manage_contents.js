

var default_d;     //default display layer

var overlays = {};    // overlay layers

var ovdef = {}     // information of overlay layers
var backGrounds = {};    // backGround layers list

var CbaseLayer;           // Current　Base Layer

var  UserList = {};       //   ユーザ名リスト

var  urlParameta = new Object;　　　//  起動引き数

var  OrgPointdata;    //  オリジナルポイントデータ

var mapSheetId;       //  シートID
var mapSheetName;     // シート名


//    引数解析
function GetURLParameta(){

     var pair=location.search.substring(1).split('&');
     for(var i=0;pair[i];i++) {
         var kv = pair[i].split('=');
           urlParameta[kv[0]]=kv[1];
       }

       mapSheetId = urlParameta["sheetid"];
       mapSheetName = urlParameta["sheetname"];
  }




//var dSearch;
function SetBaseLayers( data ){
  var baselays = data["baselayers"];
  var $baselist = $('#baselayers');

 var $dcount = 1;
  for ( let vf of baselays ) {
       //console.log( vf);
       var $checkstr = ' ';
       if ( $dcount == 1) {
         $checkstr = 'checked';
       }

       var $btn =  '<input id="' +  String($dcount) + '" name="base_layer" type="radio" value="' + String($dcount)  + '" ' + $checkstr +  '/>' +  '<label for="' +  String($dcount) +'">' + vf["name"] + '</label>';


       $( $btn ).appendTo( $baselist );


       var $bslayer = L.tileLayer(vf["url"], {
                           attribution : vf["attribute"], minZoom: vf["minzoom"], maxZoom: vf["maxzoom"], zIndex:0
                       });
         if ( vf["opacity"]){
                    $bslayer.setOpacity( parseFloat(vf["opacity"]));
                 }

       backGrounds[$dcount]= $bslayer;

       if ( $dcount == 1) {
          CbaseLayer = $bslayer;

          CbaseLayer.setZIndex(0);

          CbaseLayer.addTo(map);
       }


       $dcount++;
  }


  //     base layer radio button handler
  $( 'input[name="base_layer"]:radio' ).change( function() {


       map.removeLayer( CbaseLayer );

       // BaseMaps[$( this ).val()].addTo( map );
       CbaseLayer = backGrounds[$( this ).val()];

      CbaseLayer.setZIndex(0);
      CbaseLayer.addTo( map );

      });

  $('#baselayers').trigger("create");

}




function SetOverlayLayers( data ){

    var overlaydata = data["overlaylayers"];

    var $overlaylist = $('#overlaylist');

     var $dcount = 1;

    for ( let vf of overlaydata) {
        // console.log( vf);

        var $btn =  '<input id="ov'+ String($dcount)+ '"  name="ov_layer1" type="checkbox" value="' + String($dcount)+ '" onChange=\'changechk( this )\'   /><label for="ov'+ String($dcount)+'">' + vf["name"] +'</label>'
        $( $btn ).appendTo($overlaylist );

        var $zi = 255 - $dcount;
        var $ovl  = L.tileLayer(vf["url"],
        {    attribution : vf["attribute"], minZoom: vf["minzoom"], maxZoom: vf["maxzoom"], zIndex:$zi });

        if ( vf["opacity"]){
          $ovl.setOpacity( parseFloat(vf["opacity"]));
        }
        overlays[$dcount] = $ovl;
        ovdef[$dcount] = vf;

       $dcount++;
    }
$('#overlaylist').trigger("create");

}

//   レイヤ情報の設定
function SetLayerinfo(　mapsheetId) {


    var $overlaylist = $('#overlaylist');


    var $btn =  '<input id="ov0"  name="ov_layer1" type="checkbox" value="default_d" onChange=\'changechk( this )\'    checked /><label for="ov0">調査データ</label>'

  //   console.log( $btn );
    $( $btn ).appendTo($overlaylist );


    //  get layer information from config sheet
     url = 'getLayerSetting.php'
    $.ajax({
             url: url,
             type: "POST",
            data:{sheetid: mapsheetId},
             dataType: "json",
             success: function (data, status, xhr) {


              // console.log( data );
                SetBaseLayers( data );
               SetOverlayLayers( data );



                    },
             error: function (xhr, status, error) {
                   alert(error);

                 }
               });





}
   function  SheetListSetup(sheetid){

     //  set sheet name list
       url = 'getsheetList.php'
       $.ajax({
         url: url,
         type: "POST",
        data:{sheetid: sheetid},
         dataType: "json",
         success: function (data, status, xhr) {


             var $buttonlist = $('#sheetlist');

             var sheetnames = data['sheetnames'];

             for(let v of sheetnames ) {

               if ( v !== 'config'){   //  設定ページを除く
                    var $btn =  '<a href="JavaScript:SelectSheet(\'' + v +'\')" class="ui-btn">' + v + '</a>';

                    //   console.log( $btn );
                       $( $btn ).appendTo($buttonlist);
                 // console.log(v);
                        }

                  }
                $('#sheetlist').trigger("create");
                },
         error: function (xhr, status, error) {
               alert(error);

             }
           });

   }

//   シートデータ設定　最初の場合
  function SelectSheetInit(sheetid, sheetname){

    tgSheetname ="シート1"
    if (sheetname){
      tgSheetname = sheetname;
    }


    SelectSheet  (tgSheetname);
   }



function SelectSheet( sheetname ){
     //  set sheet name list
       url = 'getfeatures.php'
       $.ajax({
         url: url,
         type: "POST",
         data:{sheetname: sheetname, sheetid:mapSheetId},
         dataType: "json",
         success: function (data, status, xhr) {
            //  console.log( data.length)
          //    console.log( data );

            var PointACluster;
            PointACluster = CreatePointCluster( data  , PointACluster);
              //マーカークラスター設定


            if ( default_d){
                  map.removeLayer(default_d);
              }

            PointACluster.setZIndex(250);
            PointACluster.addTo(map);


　　　　　　 OrgPointdata = PointACluster;

            default_d = PointACluster;
            featureG = L.featureGroup([ default_d ]);

            overlays["default_d"] = default_d;
                FitBound();


          //  map.addLayer(PointACluster);



                },
         error: function (xhr, status, error) {
               alert(error);

             }
           });
   }



function CreatePointCluster( data, PointClusterd){
   //マーカークラスター設定
 PointClusterd = L.markerClusterGroup({
   showCoverageOnHover: false,
   spiderfyOnMaxZoom: true,
   removeOutsideVisibleBounds: true,
   disableClusteringAtZoom: 18
       });

     //  ポイント geojson 定義
   var PointArray = {
     "type": "FeatureCollection",
     "crs": { "type": "name", "properties": { "name": "urn:ogc:def:crs:OGC:1.3:CRS84" } }
   };

　　　　　　　　　　//  ポイント地物リスト
   var Features = [];
   UserList = {};   //  User 別リストの初期化


   for ( var item in data  ){
        pfeature = data[item];

        var dheader = pfeature["location"];
        var dprop   = pfeature["attribute"];

        if ( dheader ){
          xpp = dheader['x'];
          ypp = dheader['y'];



          var feature = {};

          var nproperties = {};
          var ngeometry = {};

             //  プロパティの配列化が必要
             nproperties["id"] = dheader["vkey"];
             nproperties["user"] = dheader["user"];
             nproperties["date"] = dheader["date"];

             var property_array = [];

             for ( var iprop in dprop){
               var  propd = {};
              // console.log(dprop[iprop]);
               propd['日付'] = dprop[iprop]['日付'] ;
               propd['ユーザ'] = dprop[iprop]['ユーザ'] ;
               propd['種別'] = dprop[iprop]['種別'] ;
               propd['TEXT'] = dprop[iprop]['TEXT'];
               propd['url'] = dprop[iprop]['url'] ;

               property_array.push( propd );
             }

             nproperties["proplist"] = property_array ;

             ngeometry["type"] = "Point";
             ngeometry["coordinates"] = [];

             ngeometry["coordinates"].push(xpp);
             ngeometry["coordinates"].push(ypp);

             feature["type"] = "Feature";
             feature["properties"]= nproperties;
             feature["geometry"]= ngeometry;

             　　

             Features.push(feature);

             if ( ! UserList[nproperties["user"]]){
                   UserList[nproperties["user"]] = [];
             }

             UserList[nproperties["user"]].push(feature);
           }　　//  dbheader

        //console.log( UserList);
   }  // for loop

//   条件指定用ユーザーリストの設定
   SetUserList( UserList );

//   条件指定用日付リストの設定
   SetDateList( Features );

   PointArray["features"]= Features;




   //console.log(PointArray);
   PointClusterd.addLayer(L.geoJson(PointArray,{
   onEachFeature:function (feature, layer) {
     // 地物クリック時の関数記述　プロパティが配列化した場合
          PropContents2(feature,layer);
     //var field = "id: " + feature.properties.id;
     //  layer.bindPopup(field);

    },
clickable: true
}));


 return( PointClusterd  );

}

//      日付リストの設定
function SetDateList( features ){
  var $datelist = $('#datelist');

    $datelist.empty();

    $dcount = 1;

    var  date_array = {};


    for ( let vf in  features ) {

            var datedp = features[vf]["properties"]["date"];

            var darray = datedp.split(" ");

            date_array[darray[0]] = 1;



    }


    for ( let dn in  date_array ) {
        // console.log( vf);

        dated = dn;



        var $btn =  '<input id="dt'+ String($dcount)+ '"  name="date_list" type="checkbox" value="' + dated + '"  onChange=\'changeDateStat( this )\'   checked   /><label for="dt'+ String($dcount)+'">' + dated +'</label>'
        $( $btn ).appendTo($datelist )
         $dcount++;

     }




    $('#datelist').trigger("create");

}

function SetUserList( userListA ){
  var $user_list = $('#userlist');

  $user_list.empty();

         $dcount = 1;
         for ( let vf in  userListA) {
             // console.log( vf);


             var $btn =  '<input id="us'+ String($dcount)+ '"  name="userlist" type="checkbox" value="' + vf + '"  onChange=\'changeUserStat( this )\'   checked   /><label for="us'+ String($dcount)+'">' + vf +'</label>'
             $( $btn ).appendTo($user_list )
              $dcount++;

          }
    $('#userlist').trigger("create");

   }

//    日付のチェック変更
function changeDateStat( stat ){
       ChangeReportdisplay();　　　
}

//   調査員のチェック変更
function changeUserStat( stat ){
  　　//  調査データの再構成と再描画を行う
       ChangeReportdisplay();
}

//    調査データの再描画
function  ChangeReportdisplay(){

  var n = $( "input[name='userlist']:checked").length;

  //  チェックされているユーザリスト取得
　　 var chkdef  = $("input[name='userlist']:checked" ).map(function(){
  //$(this)でjQueryオブジェクトが取得できる。val()で値をvalue値を取得。
              return $(this).val();
        }).get();

  //  チェックされている日付リスト取得
    var  datedef =　 $("input[name='date_list']:checked" ).map(function(){
      //$(this)でjQueryオブジェクトが取得できる。val()で値をvalue値を取得。
                  return $(this).val();
            }).get();


  //console.log(datedef);


  var PointCluster = L.markerClusterGroup({
    showCoverageOnHover: false,
    spiderfyOnMaxZoom: true,
    removeOutsideVisibleBounds: true,
    disableClusteringAtZoom: 18
        });

  //  ポイント geojson 定義
　　var PointArray = {
  "type": "FeatureCollection",
  "crs": { "type": "name", "properties": { "name": "urn:ogc:def:crs:OGC:1.3:CRS84" } }
　　　};

　　　var Features = [];

    for ( var userid in chkdef  ){
　　       var flist = UserList[ chkdef[userid]];

           for ( var feature in flist ){
                //  日付チェックを追加

              　var datestr = flist[feature]["properties"]["date"];

                //console.log(datestr);
                var splitd = datestr.split(" ");

                     //  指定日付がチェックされているかどうか
                if (datedef.indexOf(splitd[0]) >= 0){
                        Features.push(flist[feature]);
                }


           }
        }
    PointArray["features"]= Features;

     PointCluster.addLayer(L.geoJson(PointArray,{
    onEachFeature:function (feature, layer) {
      // 地物クリック時の関数記述　プロパティが配列化した場合
           PropContents2(feature,layer);


     },
 clickable: true
 }));


  if ( default_d){
    　　　　
              map.removeLayer(default_d);
              delete default_d;
          }

  PointCluster.setZIndex(250);
  PointCluster.addTo(map);


 　　　　

 default_d =  PointCluster;
 featureG  = L.featureGroup([ default_d ]);

overlays["default_d"] = default_d;

}


function PropContents2(feature, layer) {
       // does this feature have a property named popupContent?

      //     console.log("propcontents2");
    //   if (feature.properties && feature.properties.日付) {

          var tgtext = "";
        //  console.log(feature);
           //var kind = feature.properties.種別;

           tgtext = feature.properties.date + "<br>報告者:" +  feature.properties.user ;


               var propList = feature.properties.proplist;


              if ( propList ) {
              for ( let vf of propList ) {
            //        console.log(vf);
                   tgtext = tgtext + "<br>" + vf["日付"] ;

                    kind = vf["種別"];


                    if ( vf["url"]   ){
                     imageurl = vf["url"];

                          dlurl = imageurl;

                          mmurl = dlurl.replace('?dl=0', '');
                         mmurl = mmurl.replace('www.dropbox.com', 'dl.dropboxusercontent.com');

                          tgtext = tgtext + "<br><a href=\""+ imageurl + "\" target=\"photo\">" + imageurl + "</a>";

                           if ( kind === 'image' ) {



                          tgtext = tgtext + "<br><a href=\""+ imageurl + "\" target=\"photo\"><img src=\"" + mmurl + "\"  width=\"200\"></a>";

                        　　　　　　}



                    　　　　　}



                     if ( vf["TEXT"]  ) {
                         tgtext = tgtext +  "<br>" + vf["TEXT"]+ "<br>";

                      　　}


                   }　// proplist loop

           　　}  // if proplist


           layer.bindPopup(tgtext);
      // }  //
   }

   function  FitBound() {

     try {
           map.fitBounds(featureG.getBounds());
     }
     catch (err){
         alert("位置情報を持つ調査データがありません");
         PanCurrentLocation();
     }

   }

   // 　　主題図チェックボックスをクリックした場合の動作
   function changechk( cb ){

     var tgkey = cb.value;
     var tgstat = cb.checked;

     var tgLayer =  overlays[tgkey ];

     if ( tgstat ) {
         tgLayer.addTo( map );
     }
     else {
        map.removeLayer( tgLayer );

     }
   }



function PropContents(feature, layer) {
    // does this feature have a property named popupContent?
    if (feature.properties && feature.properties.日付) {

       var tgtext = "";

        var kind = feature.properties.種別;

        tgtext = feature.properties.日付 + "<br>" + kind + "<br>報告者:" +  feature.properties.ユーザ ;




        if ( kind === 'image' ) {

        imageurl = feature.properties.url;

        dlurl = imageurl;

        mmurl = dlurl.replace('?dl=0', '');
        mmurl = mmurl.replace('www.dropbox.com', 'dl.dropboxusercontent.com');

        tgtext = tgtext + "<br><a href=\""+ imageurl + "\" target=\"photo\">" + imageurl + "</a>";

        tgtext = tgtext + "<br><a href=\""+ imageurl + "\" target=\"photo\"><img src=\"" + mmurl + "\"  width=\"200\"></a>";

        }
        else {

        tgtext = tgtext +  "<br>" + feature.properties.テキスト + "<br>";

        }

        if ( nlj === null ) {
         }
        else  {
           if ( typeof nlj !== 'undefined' ){
            var propList = nlj[feature.properties.ユーザ ][feature.properties.uid];


           if ( propList ) {
           for ( let vf of propList ) {
                tgtext = tgtext + "<br>" + vf.date ;

                 kind = vf.kind;


                 if ( vf.url   ){
                  imageurl = vf.url;

                       dlurl = imageurl;

                       mmurl = dlurl.replace('?dl=0', '');
                      mmurl = mmurl.replace('www.dropbox.com', 'dl.dropboxusercontent.com');

                       tgtext = tgtext + "<br><a href=\""+ imageurl + "\" target=\"photo\">" + imageurl + "</a>";

                        if ( kind === 'image' ) {



                       tgtext = tgtext + "<br><a href=\""+ imageurl + "\" target=\"photo\"><img src=\"" + mmurl + "\"  width=\"200\"></a>";

                     }



                 }



                  if ( vf.text  ) {
                      tgtext = tgtext +  " " + vf.text + "<br>";

                   }
                }
              }
            }
        }


        layer.bindPopup(tgtext);
    }
}
