<?php
	/********************************************************************/
	/*                                                                  */
	/*      Copyright (C) 2004 Arash Dejkam, All Rights Reserved.       */
	/*      http://www.tracewatch.com                                   */
	/*                                                                  */
	/*      Please read the licence file distributed with this          */
	/*      file or the one available at tracewatch.com for             */
	/*      the terms under which you can use or modify this file.      */
	/*                                                                  */
	/********************************************************************/

ArdeCountry::$nameToId = array(
	 'unknown country' => 1
	,'albania' => 2
	,'algeria' => 3
	,'american samoa' => 4
	,'andorra' => 5
	,'angola' => 6
	,'anguilla' => 7
	,'antarctica' => 8
	,'antigua and barbuda' => 9
	,'argentina' => 10
	,'armenia' => 11
	,'aruba' => 12
	,'australia' => 13
	,'austria' => 14
	,'azerbaijan' => 15
	,'bahamas' => 16
	,'bahrain' => 17
	,'bangladesh' => 18
	,'barbados' => 19
	,'belarus' => 20
	,'belgium' => 21
	,'belize' => 22
	,'benin' => 23
	,'bermuda' => 24
	,'bhutan' => 25
	,'bolivia' => 26
	,'bosnia and herzegovina' => 27
	,'botswana' => 28
	,'bouvet island' => 29
	,'brazil' => 30
	,'british indian ocean territory' => 31
	,'brunei darussalam' => 32
	,'bulgaria' => 33
	,'burkina faso' => 34
	,'burundi' => 35
	,'cambodia' => 36
	,'cameroon' => 37
	,'canada' => 38
	,'cape verde' => 39
	,'cayman islands' => 40
	,'central african republic' => 41
	,'chad' => 42
	,'chile' => 43
	,'china' => 44
	,'christmas island' => 45
	,'cocos (keeling) islands' => 46
	,'colombia' => 47
	,'comoros' => 48
	,'congo' => 49
	,'congo, democratic republic of the' => 50
	,'cook islands' => 51
	,'costa rica' => 52
	,'croatia' => 53
	,'cuba' => 54
	,'cyprus' => 55
	,'czech repbulic' => 56
	,'côte d\'ivoire' => 57
	,'denmark' => 58
	,'djibouti' => 59
	,'dominica' => 60
	,'dominican repbulic' => 61
	,'east timor' => 62
	,'ecuador' => 63
	,'egypt' => 64
	,'el salvador' => 65
	,'equatorial guinea' => 66
	,'eritrea' => 67
	,'estonia' => 68
	,'ethiopia' => 69
	,'falkland islands (malvinas)' => 70
	,'faroe islands' => 71
	,'fiji' => 72
	,'finland' => 73
	,'france' => 74
	,'france, metropolitan' => 75
	,'frence guiana' => 76
	,'french polynesia' => 77
	,'french southern territories' => 78
	,'great britain' => ArdeCountry::GB
	,'gabon' => 80
	,'gambia' => 81
	,'georgia' => 82
	,'germany' => 83
	,'ghana' => 84
	,'gibraltar' => 85
	,'greece' => 86
	,'greenland' => 87
	,'grenada' => 88
	,'guadaloupe' => 89
	,'guam' => 90
	,'guatemala' => 91
	,'guinea' => 92
	,'guinea-bissau' => 93
	,'guyana' => 94
	,'haiti' => 95
	,'heard island and mcdonald islands' => 96
	,'honduras' => 97
	,'hong kong' => 98
	,'hungary' => 99
	,'iceland' => 100
	,'india' => 101
	,'indonesia' => 102
	,'iran' => 103
	,'iraq' => 104
	,'ireland' => 105
	,'israel' => 106
	,'italy' => 107
	,'jamaica' => 108
	,'japan' => 109
	,'jordan' => 110
	,'kazakhstan' => 111
	,'kenya' => 112
	,'kiribati' => 113
	,'korea, north' => 114
	,'korea, south' => 115
	,'kuwait' => 116
	,'kyrgyzstan' => 117
	,'laos' => 118
	,'latvia' => 119
	,'lebanon' => 120
	,'lesotho' => 121
	,'liberia' => 122
	,'libya' => 123
	,'liechtenstein' => 124
	,'lithuania' => 125
	,'luxembourg' => 126
	,'macau' => 127
	,'macedonia' => 128
	,'madagascar' => 129
	,'malawi' => 130
	,'malaysia' => 131
	,'maldives' => 132
	,'mali' => 133
	,'malta' => 134
	,'marshall islands' => 135
	,'martinique' => 136
	,'mauritania' => 137
	,'mauritius' => 138
	,'mayotte' => 139
	,'mexico' => 140
	,'micronesia' => 141
	,'moldova' => 142
	,'monaco' => 143
	,'mongolia' => 144
	,'montserrat' => 145
	,'morocco' => 146
	,'mozambique' => 147
	,'myanmar' => 148
	,'namibia' => 149
	,'nauru' => 150
	,'nepal' => 151
	,'netherlands' => 152
	,'netherlands antilles' => 153
	,'new caledonia' => 154
	,'new zealand' => 155
	,'nicaragua' => 156
	,'niger' => 157
	,'nigeria' => 158
	,'niue' => 159
	,'norfolk island' => 160
	,'northern mariana islands' => 161
	,'norway' => 162
	,'oman' => 163
	,'pakistan' => 164
	,'palau' => 165
	,'panama' => 166
	,'papua new guinea' => 167
	,'paraguay' => 168
	,'peru' => 169
	,'philippines' => 170
	,'pitcairn' => 171
	,'poland' => 172
	,'portugal' => 173
	,'puerto rico' => 174
	,'qatar' => 175
	,'romania' => 176
	,'russian federation' => 177
	,'rwanda' => 178
	,'réunion' => 179
	,'saint helena' => 180
	,'saint kitts and nevis' => 181
	,'saint lucia' => 182
	,'saint pierre and miquelon' => 183
	,'saint vincent and the grenadines' => 184
	,'samoa' => 185
	,'san marino' => 186
	,'saudi arabia' => 187
	,'senegal' => 188
	,'seychelles' => 189
	,'sierra leone' => 190
	,'singapore' => 191
	,'slovakia' => 192
	,'slovenia' => 193
	,'solomon islands' => 194
	,'south africa' => 195
	,'south georgia and the south sandwich islands' => 196
	,'spain' => 197
	,'sri lanka' => 198
	,'sudan' => 199
	,'suriname' => 200
	,'svalbard and jan mayen' => 201
	,'swaziland' => 202
	,'sweden' => 203
	,'switzerland' => 204
	,'syria' => 205
	,'São Tomé and Príncipe' => 206
	,'taiwan' => 207
	,'tajikistan' => 208
	,'tanzania' => 209
	,'thailand' => 210
	,'togo' => 211
	,'tokelau' => 212
	,'tonga' => 213
	,'trinidad and tobago' => 214
	,'tunisia' => 215
	,'turkey' => 216
	,'turkmenistan' => 217
	,'turks and caicos islands' => 218
	,'tuvalu' => 219
	,'uganda' => 220
	,'ukraine' => 221
	,'united arab emirates' => 222
	,'united kingdom' => ArdeCountry::UK
	,'united states' => 224
	,'united states minor and outlying islands' => 225
	,'uruguay' => 226
	,'uzbekistan' => 227
	,'vanuatu' => 228
	,'vatican city' => 229
	,'venezuela' => 230
	,'viet nam' => 231
	,'virgin islands, british' => 232
	,'virgin islands, u.s.' => 233
	,'wallis and fortuna islands' => 234
	,'western sahara' => 235
	,'yemen' => 236
	,'yugoslavia' => 237
	,'zaire' => 238
	,'zambia' => 239
	,'zimbabwe' => 240
	,'serbia and montenegro' => 244
	,'serbia' => 245
	,'montenegro' => 246
	,'palestinian territory' => 247
	,'somalia' => 248
	,'timor-leste' => 249
	,'jersey' => 250
	,'aland islands' => 251
	,'afghanistan' => 252
	,'saint martin' => 253
	,'isle of man' => 254
	,'guernsey' => 255
	,'european union' => 256
);
?>