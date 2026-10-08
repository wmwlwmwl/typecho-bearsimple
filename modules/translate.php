<?php $bsWorldLang = (array)$bsWorldLang; ?>
  <div class="foot-setting-item global">
  <div class="foot-setting-label">
    <i class="fas fa-globe"></i>
    <span>全球语言</span>
  </div>
  <select id="language-select" class="language-select">
      <option value="chinese_simplified">简体中文</option>
    <?php if(!empty($bsWorldLang[0]) && in_array('chinese_traditional',$bsWorldLang)): ?>
      <option value="chinese_traditional">繁体中文</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('german',$bsWorldLang)): ?>
      <option value="german">Deutsch</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('corsican',$bsWorldLang)): ?>
      <option value="corsican">Corsu</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('guarani',$bsWorldLang)): ?>
      <option value="guarani">Guarani</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('kinyarwanda',$bsWorldLang)): ?>
      <option value="kinyarwanda">Kinyarwanda</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('hausa',$bsWorldLang)): ?>
      <option value="hausa">Hausa</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('norwegian',$bsWorldLang)): ?>
      <option value="norwegian">Norge</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('dutch',$bsWorldLang)): ?>
      <option value="dutch">Nederlands</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('yoruba',$bsWorldLang)): ?>
      <option value="yoruba">Yoruba</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('english',$bsWorldLang)): ?>
      <option value="english">English</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('gongen',$bsWorldLang)): ?>
      <option value="gongen">गोंगेन हें नांव</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('latin',$bsWorldLang)): ?>
      <option value="latin">Latina</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('nepali',$bsWorldLang)): ?>
      <option value="nepali">नेपाली</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('french',$bsWorldLang)): ?>
      <option value="french">Français</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('czech',$bsWorldLang)): ?>
      <option value="czech">čeština</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('hawaiian',$bsWorldLang)): ?>
      <option value="hawaiian">ʻŌlelo Hawaiʻi</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('georgian',$bsWorldLang)): ?>
      <option value="georgian">ჯორჯიანი</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('russian',$bsWorldLang)): ?>
      <option value="russian">Русский</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('persian',$bsWorldLang)): ?>
      <option value="persian">فارسی</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('bhojpuri',$bsWorldLang)): ?>
      <option value="bhojpuri">भोजपुरी</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('hindi',$bsWorldLang)): ?>
      <option value="hindi">हिंदी</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('belarusian',$bsWorldLang)): ?>
      <option value="belarusian">беларускі</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('swahili',$bsWorldLang)): ?>
      <option value="swahili">Kiswahili</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('icelandic',$bsWorldLang)): ?>
      <option value="icelandic">Íslenska</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('yiddish',$bsWorldLang)): ?>
      <option value="yiddish">ייַדיש</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('twi',$bsWorldLang)): ?>
      <option value="twi">Twi</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('irish',$bsWorldLang)): ?>
      <option value="irish">Gaeilge</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('gujarati',$bsWorldLang)): ?>
      <option value="gujarati">ગુજરાતી</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('khmer',$bsWorldLang)): ?>
      <option value="khmer">ខ្មែរ</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('slovak',$bsWorldLang)): ?>
      <option value="slovak">Slovenčina</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('hebrew',$bsWorldLang)): ?>
      <option value="hebrew">עברית</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('kannada',$bsWorldLang)): ?>
      <option value="kannada">ಕನ್ನಡ</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('hungarian',$bsWorldLang)): ?>
      <option value="hungarian">Magyar</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('tamil',$bsWorldLang)): ?>
      <option value="tamil">தமிழ்</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('arabic',$bsWorldLang)): ?>
      <option value="arabic">العربية</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('bengali',$bsWorldLang)): ?>
      <option value="bengali">বাংলা</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('azerbaijani',$bsWorldLang)): ?>
      <option value="azerbaijani">Azərbaycan</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('samoan',$bsWorldLang)): ?>
      <option value="samoan">Samoan</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('afrikaans',$bsWorldLang)): ?>
      <option value="afrikaans">Afrikaans</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('indonesian',$bsWorldLang)): ?>
      <option value="indonesian">Bahasa Indonesia</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('danish',$bsWorldLang)): ?>
      <option value="danish">Dansk</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('shona',$bsWorldLang)): ?>
      <option value="shona">Shona</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('bambara',$bsWorldLang)): ?>
      <option value="bambara">Bamanankan</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('lithuanian',$bsWorldLang)): ?>
      <option value="lithuanian">Lietuvių</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('vietnamese',$bsWorldLang)): ?>
      <option value="vietnamese">Tiếng Việt</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('maltese',$bsWorldLang)): ?>
      <option value="maltese">Malti</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('turkmen',$bsWorldLang)): ?>
      <option value="turkmen">Türkmen</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('assamese',$bsWorldLang)): ?>
      <option value="assamese">অসমীয়া</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('catalan',$bsWorldLang)): ?>
      <option value="catalan">Català</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('singapore',$bsWorldLang)): ?>
      <option value="singapore">Singapore</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('cebuano',$bsWorldLang)): ?>
      <option value="cebuano">Cebuano</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('scottish-gaelic',$bsWorldLang)): ?>
      <option value="scottish-gaelic">Gàidhlig</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('sanskrit',$bsWorldLang)): ?>
      <option value="sanskrit">संस्कृत</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('polish',$bsWorldLang)): ?>
      <option value="polish">Polski</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('galician',$bsWorldLang)): ?>
      <option value="galician">Galego</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('latvian',$bsWorldLang)): ?>
      <option value="latvian">Latviešu</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('ukrainian',$bsWorldLang)): ?>
      <option value="ukrainian">Українська</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('tatar',$bsWorldLang)): ?>
      <option value="tatar">Татар</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('welsh',$bsWorldLang)): ?>
      <option value="welsh">Cymraeg</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('japanese',$bsWorldLang)): ?>
      <option value="japanese">日本語</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('filipino',$bsWorldLang)): ?>
      <option value="filipino">Filipino</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('aymara',$bsWorldLang)): ?>
      <option value="aymara">Aymara</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('lao',$bsWorldLang)): ?>
      <option value="lao">ລາວ</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('telugu',$bsWorldLang)): ?>
      <option value="telugu">తెలుగు</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('romanian',$bsWorldLang)): ?>
      <option value="romanian">Română</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('haitian_creole',$bsWorldLang)): ?>
      <option value="haitian_creole">Kreyòl Ayisyen</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('dogrid',$bsWorldLang)): ?>
      <option value="dogrid">डोग्रिड</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('swedish',$bsWorldLang)): ?>
      <option value="swedish">Svenska</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('maithili',$bsWorldLang)): ?>
      <option value="maithili">मैथिली</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('thai',$bsWorldLang)): ?>
      <option value="thai">ไทย</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('armenian',$bsWorldLang)): ?>
      <option value="armenian">Հայերեն</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('burmese',$bsWorldLang)): ?>
      <option value="burmese">မြန်မာ</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('pashto',$bsWorldLang)): ?>
      <option value="pashto">پښتو</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('hmong',$bsWorldLang)): ?>
      <option value="hmong">Hmoob</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('dhivehi',$bsWorldLang)): ?>
      <option value="dhivehi">ދިވެހި</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('luxembourgish',$bsWorldLang)): ?>
      <option value="luxembourgish">Lëtzebuergesch</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('sindhi',$bsWorldLang)): ?>
      <option value="sindhi">سنڌي</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('kurdish',$bsWorldLang)): ?>
      <option value="kurdish">Kurdî</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('turkish',$bsWorldLang)): ?>
      <option value="turkish">Türkçe</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('macedonian',$bsWorldLang)): ?>
      <option value="macedonian">Македонски</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('bulgarian',$bsWorldLang)): ?>
      <option value="bulgarian">Български</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('malay',$bsWorldLang)): ?>
      <option value="malay">Bahasa Melayu</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('luganda',$bsWorldLang)): ?>
      <option value="luganda">Luganda</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('marathi',$bsWorldLang)): ?>
      <option value="marathi">मराठी</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('estonian',$bsWorldLang)): ?>
      <option value="estonian">Eesti</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('malayalam',$bsWorldLang)): ?>
      <option value="malayalam">മലയാളം</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('slovene',$bsWorldLang)): ?>
      <option value="slovene">Slovenščina</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('urdu',$bsWorldLang)): ?>
      <option value="urdu">اردو</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('portuguese',$bsWorldLang)): ?>
      <option value="portuguese">Português</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('igbo',$bsWorldLang)): ?>
      <option value="igbo">Igbo</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('kurdish_sorani',$bsWorldLang)): ?>
      <option value="kurdish_sorani">کوردی-سۆرانی</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('oromo',$bsWorldLang)): ?>
      <option value="oromo">Oromoo</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('greek',$bsWorldLang)): ?>
      <option value="greek">Ελληνικά</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('spanish',$bsWorldLang)): ?>
      <option value="spanish">Español</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('frisian',$bsWorldLang)): ?>
      <option value="frisian">Frysk</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('somali',$bsWorldLang)): ?>
      <option value="somali">Soomaali</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('amharic',$bsWorldLang)): ?>
      <option value="amharic">አማርኛ</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('nyanja',$bsWorldLang)): ?>
      <option value="nyanja">Nyanja</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('punjabi',$bsWorldLang)): ?>
      <option value="punjabi">ਪੰਜਾਬੀ</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('basque',$bsWorldLang)): ?>
      <option value="basque">Euskara</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('italian',$bsWorldLang)): ?>
      <option value="italian">Italiano</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('albanian',$bsWorldLang)): ?>
      <option value="albanian">Shqip</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('korean',$bsWorldLang)): ?>
      <option value="korean">한국어</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('tajik',$bsWorldLang)): ?>
      <option value="tajik">Тоҷикӣ</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('finnish',$bsWorldLang)): ?>
      <option value="finnish">Suomi</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('kyrgyz',$bsWorldLang)): ?>
      <option value="kyrgyz">Кыргызча</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('ewe',$bsWorldLang)): ?>
      <option value="ewe">Eʋegbe</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('croatian',$bsWorldLang)): ?>
      <option value="croatian">Hrvatski</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('creole',$bsWorldLang)): ?>
      <option value="creole">Kreyòl</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('quechua',$bsWorldLang)): ?>
      <option value="quechua">Quechua</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('bosnian',$bsWorldLang)): ?>
      <option value="bosnian">Bosanski</option>
    <?php endif; ?>
    <?php if(!empty($bsWorldLang[0]) && in_array('maori',$bsWorldLang)): ?>
      <option value="maori">Māori</option>
    <?php endif; ?>
    <?php if(empty($bsWorldLang)): ?>
      <option disabled>暂未找到可选语言</option>
    <?php endif; ?>
  </select>
</div>
<script>
$(document).ready(function() {
  const savedLanguage = localStorage.getItem('selectedLanguage');

  if (savedLanguage) {
    $('#language-select').val(savedLanguage);
  }

  // 监听语言选择的变化
  $('#language-select').on('change', function () {
    const selectedLanguage = $(this).val();

    localStorage.setItem('selectedLanguage', selectedLanguage);

    translate.changeLanguage(selectedLanguage);
  });
});
</script>