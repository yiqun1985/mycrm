<?php
// 引入数据库连接文件
require_once 'db_connection.php';

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取表单数据
    $productNameCN = $_POST['ProductNameCN'];
    $productNameEN = $_POST['ProductNameEN'];
    $costPrice = $_POST['CostPrice'];
    $packageQuantity = $_POST['PackageQuantity'];
    $outerBoxLength = $_POST['OuterBoxLength'];
    $outerBoxWidth = $_POST['OuterBoxWidth'];
    $outerBoxHeight = $_POST['OuterBoxHeight'];
    $outerBoxGrossWeight = $_POST['OuterBoxGrossWeight'];
    $productDescription = $_POST['ProductDescription'];
    $productCategory = $_POST['ProductCategory'];
    $productSubCategory = $_POST['ProductSubCategory'];
    $productSmallCategory = $_POST['ProductSmallCategory'];
    $imageUrl = '';
    $isStaged = isset($_POST['stash']) ? 1 : 0;

    // 处理图片上传
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageName = uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imagePath = 'productIMG/' . $imageName;

        if (move_uploaded_file($imageTmpName, $imagePath)) {
            $imageUrl = $imagePath;
        } else {
            echo "无法保存图片，请检查文件夹权限。";
        }
    }

    // 自动生成 SKU
    if (!$isStaged) {
        $categoryLetter = substr($productCategory, 0, 1);
        $subCategoryLetter = substr($productSubCategory, 0, 1);
        $smallCategoryLetter = substr($productSmallCategory, 0, 1);
        $query = "SELECT MAX(SKU) as max_sku FROM product WHERE SKU LIKE '$categoryLetter$subCategoryLetter$smallCategoryLetter%'";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        $maxSku = $row['max_sku'];
        if ($maxSku) {
            $lastNumber = intval(substr($maxSku, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        $sku = $categoryLetter . $subCategoryLetter . $smallCategoryLetter . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        // 检查 SKU 是否已经存在
        $checkQuery = "SELECT COUNT(*) as count FROM product WHERE SKU = '$sku'";
        $checkResult = mysqli_query($conn, $checkQuery);
        $checkRow = mysqli_fetch_assoc($checkResult);
        if ($checkRow['count'] > 0) {
            echo "错误: SKU $sku 已经存在，请检查数据。";
            mysqli_close($conn);
            exit;
        }
    } else {
        $sku = null;
    }

    // 自动翻译产品英文名称
    if (empty($productNameEN) && !$isStaged) {
        $apiUrl = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=zh-CN&tl=en&dt=t&q=' . urlencode($productNameCN);
        $response = @file_get_contents($apiUrl);
        if ($response !== false) {
            $data = json_decode($response, true);
            $productNameEN = $data[0][0][0];
        } else {
            echo "无法获取翻译结果，请检查网络连接。";
        }
    }

    // 插入数据到数据库
    $sql = "INSERT INTO product (ProductNameCN, ProductNameEN, CostPrice, PackageQuantity, OuterBoxLength, OuterBoxWidth, OuterBoxHeight, OuterBoxGrossWeight, ProductDescription, ProductCategory, ProductSubCategory, ProductSmallCategory, ImageUrl, SKU, is_staged) 
            VALUES ('$productNameCN', '$productNameEN', $costPrice, $packageQuantity, $outerBoxLength, $outerBoxWidth, $outerBoxHeight, $outerBoxGrossWeight, '$productDescription', '$productCategory', '$productSubCategory', '$productSmallCategory', '$imageUrl', '$sku', $isStaged)";

    if (mysqli_query($conn, $sql)) {
        if ($isStaged) {
            echo "数据暂存成功！";
        } else {
            echo "产品录入成功！";
        }
    } else {
        echo "错误: " . $sql . "<br>" . mysqli_error($conn);
    }
}

// 关闭数据库连接
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>产品录入</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <h2>产品录入</h2>
    

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
    <div class="form-row">
        <div class="form-col">
            <label for="ProductNameCN">产品中文名称:</label>
            <input type="text" id="ProductNameCN" name="ProductNameCN">
        </div>
        <div class="form-col">
            <label for="ProductNameEN">产品英文名称:</label>
            <input type="text" id="ProductNameEN" name="ProductNameEN">
        </div>
        <div class="form-col">
            <label for="CostPrice">成本价格:</label>
            <input type="number" step="0.01" id="CostPrice" name="CostPrice">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-col">
            <label for="PackageQuantity">包装数量:</label>
            <input type="number" id="PackageQuantity" name="PackageQuantity">
        </div>
        <div class="form-col">
            <label for="OuterBoxLength">外箱长度:</label>
            <input type="number" step="0.01" id="OuterBoxLength" name="OuterBoxLength">
        </div>
        <div class="form-col">
            <label for="OuterBoxWidth">外箱宽度:</label>
            <input type="number" step="0.01" id="OuterBoxWidth" name="OuterBoxWidth">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-col">
            <label for="OuterBoxHeight">外箱高度:</label>
            <input type="number" step="0.01" id="OuterBoxHeight" name="OuterBoxHeight">
        </div>
        <div class="form-col">
            <label for="OuterBoxGrossWeight">外箱毛重:</label>
            <input type="number" step="0.01" id="OuterBoxGrossWeight" name="OuterBoxGrossWeight">
        </div>
        <div class="form-col">
            <label for="ProductDescription">产品描述:</label>
            <textarea id="ProductDescription" name="ProductDescription"></textarea>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-col">
            <label for="ProductCategory">产品大类:</label>
            <input type="text" id="ProductCategory" name="ProductCategory">
        </div>
        <div class="form-col">
            <label for="ProductSubCategory">产品二级类目:</label>
            <input type="text" id="ProductSubCategory" name="ProductSubCategory">
        </div>
        <div class="form-col">
            <label for="ProductSmallCategory">产品小类:</label>
            <input type="text" id="ProductSmallCategory" name="ProductSmallCategory">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-col">
            <label for="image">图片:</label>
            <input type="file" id="image" name="image">
        </div>
    </div>
    
    <input type="submit" value="提交">
    <input type="submit" name="stash" value="暂存">
</form>
</body>

</html>