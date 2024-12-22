export function splitWithDelimiter(str, delimiter) {
    const parts = str.split(delimiter);
    const result = [];

    for (let i = 0; i < parts.length; i++) {
        result.push(parts[i]); // Thêm phần đã tách vào mảng
        if (i < parts.length - 1) {
            result.push(delimiter); // Thêm delimiter vào giữa các phần
        }
    }

    return result;
}


export function findAllIndexes(str, substring) {
    const indexes = [];
    let startIndex = 0;

    while (startIndex < str.length) {
        const index = str.indexOf(substring, startIndex);
        if (index === -1) {
            break; // Không tìm thấy thêm substring nào nữa
        }
        indexes.push(index); // Lưu vị trí tìm được
        startIndex = index + substring.length; // Tiếp tục tìm sau vị trí hiện tại
    }

    return indexes;
}
