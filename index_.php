<?php
set_time_limit(3600000);

class KyNu {
  private $mUrl = 'https://kynu.net/_api/escort/directories';
  private $mDirectory = '1_directory.txt';
  private $mLink = '2_link.txt';
  private $mProduct = '3_product.txt';
  private $mAllLink = '4_allLink.txt';
  private $mAllID = '5_allID.txt';
  private $mAllImageUrl = '6_allImageUrl.txt';

  function getDirectory() {
    $webKyNu = json_decode(file_get_contents($this->mUrl));
    $webKyNu = $webKyNu->data[0]->children;
    $tinhArr = array();
    foreach ($webKyNu as $tinh) {
      $tenTinh = $tinh->short_name;
      $quanArr = array();
      if (isset($tinh->children)) {
        foreach ($tinh->children as $quan) {
          $quanArr[] = $quan->short_name;
        }
      }
      $tinhArr[$tenTinh] = $quanArr;
    }
    file_put_contents($this->mDirectory, json_encode($tinhArr));
    echo $this->mDirectory;
  }

  function getLinkFromDirectory() {
    $directory = json_decode(file_get_contents($this->mDirectory), true);
    $link = array();
    foreach ($directory as $tinh => $quans) {
      if (count($quans)) {
        foreach ($quans as $quan) {
          $link[$tinh][$quan] = "https://kynu.net/_api/escort/products?districtCode=$quan&mode=directory&orderBy=byTime";
        }
      } else {
        $link[$tinh] = "https://kynu.net/_api/escort/products?cityCode=$tinh&mode=directory&orderBy=byTime";
      }
    }
    file_put_contents($this->mLink, json_encode($link));
    echo $this->mLink;
  }

  function getProductListFromLink() {
    $link = json_decode(file_get_contents($this->mLink), true);
    $product = array();
    $allLink = array();
    foreach ($link as $tinh => $quan) {
      if (is_array($quan)) {
        foreach ($quan as $tenQuan => $api) {
          $i = 0;
          $tmp = array();
          while (true) {
            $tmpAPI = $api . '&offset=' . $i;
            if (count(json_decode(file_get_contents($tmpAPI), true))) {
              $tmp[] = $tmpAPI;
              $allLink[] = $tmpAPI;
              $i = $i + 20;
            } else {
              break;
            }
          }
          $product[$tinh][$tenQuan][] = $tmp;
        }
      } else {
        $i = 0;
        $tmp = array();
        while (true) {
          $tmpAPI = $quan . '&offset=' . $i;
          if (count(json_decode(file_get_contents($tmpAPI), true))) {
            $tmp[] = $tmpAPI;
            $allLink[] = $tmpAPI;
            $i++;
          } else {
            break;
          }
        }
        $product[$tinh][$tenQuan][] = $tmp;
      }
    }
    file_put_contents($this->mProduct, json_encode($product));
    file_put_contents($this->mAllLink, json_encode($allLink));
    echo $this->mProduct;
    echo $this->mAllLink;
  }

  function getAllIDFromAllLink() {
    $links = json_decode(file_get_contents($this->mAllLink), true);
    $allID = array();
    foreach ($links as $link) {
      $tmp = json_decode(file_get_contents($link), true);
      foreach ($tmp as $tmpObj) {
        $allID[] = $tmpObj['id'];
      }
    }
    file_put_contents($this->mAllID, json_encode($allID));
    echo $this->mAllID;
  }

  function getDetailFromID() {
    $allID = json_decode(file_get_contents($this->mAllID), true);
    $allImageUrl = array();
    foreach ($allID as $id) {
      $link = 'https://kynu.net/_api/escort/products/' . $id;
      $detail = json_decode(file_get_contents($link), true);
      foreach ($detail['images'] as $image) {
        $allImageUrl[] = $image['data']['dimensions']['original']['url'];
      }
    }
    file_put_contents($this->mAllImageUrl, json_encode($allImageUrl));
    echo $this->mAllImageUrl;
  }

  function saveImages()
  {
    $allImageUrl = json_decode(file_get_contents($this->mAllImageUrl), true);
    $countAll = count($allImageUrl);
    $i = (int) file_get_contents('0i.txt');
    for ($i; $i < $countAll; $i++) {
      $this->saveImage($allImageUrl[$i]);
      file_put_contents('0i.txt', ($i + 1));
    }
  }

  function saveImage($url) {
    if (!is_dir('images')) {
      mkdir('images');
    }
    $data = file_get_contents($url);
    if ($data) {
      file_put_contents('images/' . basename($url), $data);
      $cmd = "cd ~; cd workspace/images; gdrive upload --parent 0B2nc-Pxpt1XXRWs2NkJUa2kwYnc " . basename($url) . ";";
      exec($cmd);
      unlink('images/' . basename($url));
      echo "\n" . basename($url) . "\n";
    }
  }
}

$test = new KyNu();
$test->getDirectory();
$test->getLinkFromDirectory();
$test->getProductListFromLink();
$test->getAllIDFromAllLink();
$test->getDetailFromID();
//$test->saveImages();