<!DOCTYPE  html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
      <title>Rider# {{$contract->rider->rider_id}} Bike Handing Over</title>
      <style type="text/css"> * {margin:0; padding:0; text-indent:0; }
         .s1 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 18pt; }
         .s2 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 11pt; }
         h4 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 10pt; }
         .s3 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 10pt; }
         .s4 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }
         .s5 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }
         .s6 { color: black; font-family:"Times New Roman", serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }
         .s7 { color: #15C; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: underline; font-size: 10pt; }
         .s8 { color: #1F1F1F; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }
         .s9 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }
         .s10 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 10pt; }
         p { color: black; font-family:Cambria, serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; margin:0pt; }
         h3 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 11pt; }
         h1 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 13pt; }
         .s11 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11.5pt; }
         .s13 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 11.5pt; }
         .s14 { color: #15C; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: underline; font-size: 11.5pt; }
         .s15 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11.5pt; }
         .s16 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 13pt; }
         h2 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 11.5pt; }
         .s18 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11.5pt; }
         table, tbody {vertical-align: top; overflow: visible; }
      </style>
   </head>
   <body style="padding:5px;" >
    {{-- <table width="100%" style="font-family: sans-serif;">
        <tr>
            <td width="33.33%">&nbsp;<img src="/public/dist/img/print-logo.png" width="100" /> </td>
            <td width="33.33%" style="text-align: center;"><h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 12px;">Express Fast Delivery Service</h4>
                <p style="margin-bottom: 5px;font-size: 12px;margin-top: 5px;">Office No. 305, Al Rubaya Building Damascus Street Al Qusais 2 Dubai U.A.E</p>
                <p style="margin-bottom: 5px;font-size: 12px;margin-top: 5px;"> TRN 1005392707000003</p></td>
            <td width="33.33%" style="text-align: right;"></td>
        </tr>
        <tr style="text-align: center;">
            <td colspan="3"><h4 style="margin-bottom: 0px;margin-top: 5px;font-size: 12px;border-bottom: 1px solid #000;border-top: 1px solid #000;padding: 3px 0px;">Bike Handing Over</h4></td>
        </tr>
    </table> --}}

    <div style="height: 130px;">&nbsp;</div>
      <p style="padding-left: 165pt;text-indent: 0pt;text-align: left;"></p>
      <p class="s2" style="padding-left: 8pt;text-indent: 0pt;text-align: left;">&quot;Rider&quot; Data</p>
      <table style="border-collapse:collapse;width:100%;" >
        <tr>
            <th style="padding:5px;">Rider Status:</th>
            <td style="padding:5px;">{{App\Helpers\General::RiderStatus(@$contract->rider->status)}}</td>
            <th style="padding:5px 5px 5px 50px;">Supervisor:</th>
            <td style="padding:5px;">{{@$contract->rider->fleet_supervisor}}</td>
            <th style="padding:5px 5px 5px 150px;">Date:</th>
            <td style="padding:5px 5px 5px 5px;">{{@$contract->note_date}}</td>
        </tr>
      </table>
      <p style="text-indent: 0pt;text-align: left;"><br/></p>
      <table style="border-collapse:collapse;width:100%;" cellspacing="0">
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Name</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->name}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">RIDER I,D.</p>
            </td>
            <td style="width:161pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->rider_id}}</p>
            </td>
         </tr>
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Emirates ID.</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->emirate_id}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Passport No.</p>
            </td>
            <td style="width:161pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->passport}}</p>
            </td>
         </tr>
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Phone No.</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->personal_contact}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">License No.</p>
            </td>
            <td style="width:161pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->license_no}}</p>
            </td>
         </tr>
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Email I,d.</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->personal_email}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Emirate.</p>
            </td>
            <td style="width:161pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->emirate_hub}}</p>
            </td>
         </tr>
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Vendor.</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->rider->vendor->name}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Salary Model.</p>
            </td>
            <td style="width:161pt;border:1px solid #000000;">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">Commission</p>
            </td>
         </tr>
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Refrence No.</p>
            </td>
            <td style="width:472pt;border:1px solid #000000;" colspan="3">
               <p style="text-indent: 0pt;text-align: left;"><br/></p>
            </td>
         </tr>
      </table>
      <h4 style="padding-top: 1pt;padding-bottom: 1pt;padding-left: 8pt;text-indent: 0pt;text-align: left;">Mobile Sim Detail :</h4>
      <table style="border-collapse:collapse;width:100%;" cellspacing="0">
         <tr style="height:16pt">
            <td style="width:159pt;border:1px solid #000000;">
               <p class="s6" style="padding-top: 1pt;padding-left: 20pt;text-indent: 0pt;line-height: 13pt;text-align: left;">
                 <span class="s3"><input type="checkbox" style="margin:5px;" /> Company Sim Number.</span></p>
            </td>
            <td style="width:160pt;border:1px solid #000000;">
               <p style="text-indent: 0pt;text-align: left;">{{@$contract->rider->sims->sim_number}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 8pt;text-indent: 0pt;text-align: left;">EMI Number.</p>
            </td>
            <td style="width:161pt;border:1px solid #000000;">
               <p style="text-indent: 0pt;text-align: left;">{{@$contract->rider->sims->sim_emi}}</p>
            </td>
         </tr>
      </table>
      <h4 style="padding-top: 1pt;padding-bottom: 1pt;padding-left: 8pt;text-indent: 0pt;text-align: left;">Details of Bike:</h4>
      <table style="border-collapse:collapse;" cellspacing="0">
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 2pt;text-indent: 0pt;text-align: left;"><span class="s7">T.C.No</span>.</p>
            </td>
            <td style="width:175pt;border:1px solid #000000;" colspan="2">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->traffic_file_number}}</p>
            </td>
            <td style="width:92pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Bike Plate No.</p>
            </td>
            <td style="width:205pt;border:1px solid #000000;" colspan="2">
               <p style="text-indent: 0pt;text-align: left;"></p>
               <p class="s3" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->plate}}</p>
            </td>
         </tr>
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 2pt;text-indent: 0pt;text-align: left;">Model No.</p>
            </td>
            <td style="width:175pt;border:1px solid #000000;" colspan="2">
               <p class="s8" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->model}}</p>
            </td>
            <td style="width:92pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Bike</p>
            </td>
            <td style="width:205pt;border:1px solid #000000;" colspan="2">
               <p class="s4" style="padding-top: 1pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->model_type}}</p>
            </td>
         </tr>
         <tr style="height:16pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 2pt;text-indent: 0pt;text-align: left;">Engine No.</p>
            </td>
            <td style="width:175pt;border:1px solid #000000;" colspan="2">
               <p style="text-indent: 0pt;text-align: left;"/>
               <p class="s4" style="padding-top: 1pt;padding-left: 55pt;text-indent: 0pt;text-align: left;">{{@$contract->bike->engine}}</p>
            </td>
            <td style="width:92pt;border:1px solid #000000;">
               <p class="s3" style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Chassis No.</p>
            </td>
            <td style="width:205pt;border:1px solid #000000;" colspan="2">
               <p class="s4" style="padding-top: 1pt;padding-left: 52pt;text-indent: 0pt;text-align: left;">{{@$contract->bike->chassis_number}}</p>
            </td>
         </tr>
         <tr style="height:14pt">
            <td style="width:553pt;border:1px solid #000000;" colspan="6">
               <p style="text-indent: 0pt;text-align: left;"></p>
               <p class="s3" style="padding-left: 56pt;text-indent: 0pt;line-height: 12pt;text-align: center;">
                  Motorcycle
                  <span><input type="checkbox" style="margin:5px;" />
               </span></p>
            </td>
         </tr>
         <tr style="height:13pt">
            <td style="width:546pt;border-top-style:solid;border-top-width:1pt;border:1px solid #000000;;border-right-color:#FFFFFF" colspan="5">
               <p style="text-indent: 0pt;text-align: left;"><br/></p>
            </td>
            <td style="width:7pt;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-right-style:solid;border-right-width:1pt">
               <p style="text-indent: 0pt;text-align: left;"><br/></p>
            </td>
         </tr>
         <tr>
            <td colspan="6">
                <table style="border-collapse:collapse;margin-top:5px;width:100%;">
                    <tr style="height:13pt">
                        <td style="width:160pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 11pt;text-align: left;">Dent</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                           <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                        </td>
                        <td style="width:154pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 11pt;text-align: left;">Mobile Holder</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:153pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 11pt;text-align: left;">Crash Gurad</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                     </tr>
                     <tr style="height:13pt">
                        <td style="width:160pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">Scratch</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:154pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">Tool Kit</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:153pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">Helmet</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                     </tr>
                     <tr style="height:13pt">
                        <td style="width:160pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">Major Damage</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:154pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">Reflecting Sticher</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:153pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">Stand</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                     </tr>
                     <tr style="height:13pt">
                        <td style="width:160pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">Delivery Box</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:154pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">Motorcycle Seat</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:153pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 12pt;text-align: left;">RTA Fine</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                     </tr>
                     <tr style="height:13pt">
                        <td style="width:160pt;border:1px solid #000000;">
                           <p style="text-indent: 0pt;text-align: left;"><br/></p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:154pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 11pt;text-align: left;">Fuel Card</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:153pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 11pt;text-align: left;">Salik</p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                     </tr>
                     <tr style="height:13pt">
                        <td style="width:160pt;border:1px solid #000000;">
                           <p style="text-indent: 0pt;text-align: left;"><br/></p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:154pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 11pt;text-align: left;"></p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                        <td style="width:153pt;border:1px solid #000000;">
                           <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 11pt;text-align: left;"></p>
                        </td>
                        <td style="width:7pt;border:1px solid #000000;">
                            <p style="text-indent: 0pt;text-align: left;"><input type="checkbox" style="margin:5px;" /></p>
                         </td>
                     </tr>

                </table>
            </td>
         </tr>
         <tr>
            <td colspan="6">      <p class="s10" style="padding-left: 16pt;text-indent: 0pt;text-align: center;">Remarks</p>
            </td>
         </tr>
         <tr>
            <td colspan="6">
                <table style="border:1px solid #000000;width:100%;margin-top:1px;">
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <table style="border:1px solid #000000;width:100%;">
                                <tr >
                                    <td  style="border-bottom:1px solid #000000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #000000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #000000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #000000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #000000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                 </table>
            </td>
         </tr>
         <tr>
            <td colspan="6">
                <p style="padding-left: 17pt;padding-top:10px;text-indent: 0pt;text-align: center;">I have thoroughly Checked and understood the present condition and damage on the motorcycle. I will be responsible for any new damagesand shall bear all expenses for repair of the motorcycle, I will be responsible for any new damages as per the terms and condition mentioned on the handing over Document.</p>

            </td>
         </tr>
         <tr>
            <td colspan="6" >
                <p style="text-indent: 0pt;text-align: left;"><br/></p>
                <p style="text-indent: 0pt;text-align: left;"><br/></p>
                <p style="text-indent: 0pt;text-align: left;"><br/></p>
                <p style="text-indent: 0pt;text-align: left;"><br/></p>
                <table style="width: 100%;text-align:center;">
                    <tr>
                        <td>
                            <p><b>Motor Mechanic</b><br/>
                                Muhammad Sufyan Akbar Ali</p>
                        </td>
                        <td>
                            <p><b>Rider Signature</b><br/>
                            {{@$contract->rider->name}}</p>
                        </td>
                        <td>
                            <p><b>Supervisor Signature</b><br/>
                                {{@$contract->rider->fleet_supervisor}}</p>
                        </td>
                    </tr>
                </table>


      <p style="text-indent: 0pt;text-align: left;page-break-after: always;"><br/></p>
      {{-- <table width="100%" style="font-family: sans-serif;">
        <tr>
            <td width="33.33%">&nbsp;<img src="/public/dist/img/print-logo.png" width="100" /> </td>
            <td width="33.33%" style="text-align: center;"><h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 12px;">Express Fast Delivery Service</h4>
                <p style="margin-bottom: 5px;font-size: 12px;margin-top: 5px;">Office No. 305, Al Rubaya Building Damascus Street Al Qusais 2 Dubai U.A.E</p>
                <p style="margin-bottom: 5px;font-size: 12px;margin-top: 5px;"> TRN 1005392707000003</p></td>
            <td width="33.33%" style="text-align: right;"></td>
        </tr>

    </table> --}}
    <div style="height: 130px;">&nbsp;</div>
      <h1 style="padding-left: 8pt;text-indent: 0pt;text-align: left;">Declaration:                                     Date: {{@$contract->note_date}}</h1>
      <p class="s11" style="padding-top: 9pt;padding-left: 8pt;text-indent: 0pt;text-align: left;">Confirmation of receipt of a motorcycle “<b>Bike</b>”: I hereby certify, I am the</p>
      <p style="text-indent: 0pt;text-align: left;"><br/></p>
            </td>
         </tr>

      </table>


      <table style="border-collapse:collapse;width:100%;" cellspacing="0">
         <tr style="height:22pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Name</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 39pt;text-indent: 0pt;text-align: left;">{{@$contract->rider->name}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Emirates I,D</p>
            </td>
            <td style="width:168pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 34pt;text-indent: 0pt;text-align: left;">{{@$contract->rider->emirate_id}}</p>
            </td>
         </tr>
      </table>
      <p class="s11" style="padding-top: 3pt;padding-left: 8pt;text-indent: 0pt;text-align: left;">I have received the bike number <b>details of bike:</b></p>
      <p style="text-indent: 0pt;text-align: left;"><br/></p>
      <table style="border-collapse:collapse;width:100%;" cellspacing="0">
         <tr style="height:22pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 1pt;text-indent: 0pt;text-align: left;"><span class="s14">T.C.No</span>.</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p class="s15" style="padding-top: 3pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->traffic_file_number}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Bike Plate No.</p>
            </td>
            <td style="width:168pt;border:1px solid #000000;">
               <p class="s15" style="padding-top: 3pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->plate}}</p>
            </td>
         </tr>
         <tr style="height:22pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Model No.</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p class="s15" style="padding-top: 3pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->model}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Bike</p>
            </td>
            <td style="width:168pt;border:1px solid #000000;">
               <p class="s15" style="padding-top: 3pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->model_type}}</p>
            </td>
         </tr>
         <tr style="height:22pt">
            <td style="width:81pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Engine No.</p>
            </td>
            <td style="width:238pt;border:1px solid #000000;">
               <p class="s15" style="padding-top: 3pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->engine}}</p>
            </td>
            <td style="width:73pt;border:1px solid #000000;">
               <p class="s13" style="padding-top: 3pt;padding-left: 1pt;text-indent: 0pt;text-align: left;">Chassis No.</p>
            </td>
            <td style="width:168pt;border:1px solid #000000;">
               <p class="s15" style="padding-top: 3pt;text-indent: 0pt;text-align: center;">{{@$contract->bike->chassis_number}}</p>
            </td>
         </tr>
         <tr style="height:165pt">
            <td style="width:560pt;border:1px solid #000000;" colspan="4">
               <p class="s16" style="padding-top: 11pt;padding-left: 1pt;padding-right: 11pt;text-indent: 0pt;text-align: left;">I undertake to preserve and use the bike for the purpose for which I received it and acknowledge that I am fully responsible for its maintenance and care. I commit to return the bike to the company upon its request or at the end of the contract, in the same condition as I received it.</p>
               <p class="s16" style="padding-top: 14pt;padding-left: 1pt;padding-right: 11pt;text-indent: 0pt;text-align: left;">In the event of non-return, I agree to pay an amount of <b>AED 8,000 </b>for the bike and <b>AED 2,000 </b>for Noon Assets (Delivery Bag &amp; Noon Uniform with accessories), totaling <b>AED 10,000</b>.</p>
               <p class="s16" style="padding-top: 8pt;padding-left: 1pt;padding-right: 18pt;text-indent: 0pt;text-align: justify;">I acknowledge that it is my responsibility to return the bike to the company upon termination of employment. Should I fail to do so, I shall be liable to pay the monthly rent of <b>AED 600</b>, any RTA fines, Salik charges, and any damages to the bike until it is returned in good working condition.</p>
            </td>
         </tr>
      </table>
      <p style="padding-top: 5pt;text-indent: 0pt;text-align: left;"><br/></p>
      <h2 style="padding-left: 8pt;text-indent: 0pt;text-align: left;">IN WITNESS WHEREOF</h2>
      <p class="s18" style="padding-left: 13pt;text-indent: 0pt;text-align: left;">the Parties have executed this Contract as of the date first indicated above</p>
      <p style="text-indent: 0pt;text-align: left;"><br/></p>
      <table style="border-collapse: collapse;width: 100%;text-align:center;">
        <tr>
            <td><b>Express Fast Delivery Service Name & Signature</b><br/></td>
            <td>
                <p>
                    <b>Fingerprint</b><br/>
                    (Thumbprint of the right hand)<br/>
                    <br/><br/><br/><br/>
                    <br/><br/><br/><br/>
                    <b>Name & Signature</b><br/>
                    <b>{{@$contract->rider->name}}</b>

                </p>
            </td>
        </tr>

      </table>
      <script type="text/javascript">
        window.print();
        //window.onfocus=function(){ window.close();}
        </script>
   </body>
</html>
