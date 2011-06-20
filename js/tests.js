<!--
    /* tests.js (v1.0 - 2007/06/18)
     * ********************************************************************* *
     * by Arkaitz Garro, June 2007                                           *
     * Copyright (c) 2007 Arkaitz Garro. All Rights Reserved.                *
     *                                                                       *
     * This code is free software; you can redistribute it and/or modify     *
     * it under the terms of the GNU General Public License as published by  *
     * the Free Software Foundation; either version 2 of the License, or     *
     * (at your option) any later version.                                   *
     *                                                                       *
     * This program is distributed in the hope that it will be useful,       *
     * but WITHOUT ANY WARRANTY; without even the implied warranty of        *
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
     * GNU General Public License for more details:                          *
     *                                                                       *
     *          http://www.gnu.org/copyleft/gpl.html                         *
     * ********************************************************************* *
     * This JavaScript add / delete tests dynamicaly                         *
     *                                                                       *
     * @author Arkaitz Garro                                                 *
     * @package epaile                                                       *
     * ********************************************************************* *
     */
  
//translate status's meaning into human readble.
function translate_info(var status) {
    var msg = null;
    //TODO 这里应该使用语言里的翻译输出，但是js文件里不知道能不能调用moodle的函数，所以先不用了
    switch(status) {
        case 0:
            msg = "程序尚未编译运行，正在队列 ...";
            break;
        case 1:
            msg = "程序运行通过 ...";
            break;
        case 2:
            msg = "程序运行时中断 ...";
            break;
        case 3:
            msg = "程序编译错误 ...";
            break;
        case 4:
            msg = "程序编译成功 ...";
            break;
        case 5:
            msg = "程序运行时超过最大内存限制 ...";
            break;
        case 6:
            msg = "程序运行时超过最大CPU限制 ...";
            break;
        case 7:
            msg = "程序陈述出错...";
            break;
        case 8:
            msg = "程序使用受限制的函数 ...";
            break;
        case 9:
            msg = "程序运行错误 ...";
            break;
        case 10:
            msg = "程序运行时超出最长时间限制 ...";
            break;
        case 11:
            msg = "程序运行输出结果与用例输出不一致 ...";
            brak;
        case 21:
            msg = "程序内部出错 ...";
            break;
        case 22:
            msg = "程序正在运行中 ...";
            break;
        case 23:
            msg = "程序运行结果有多个状态，即有多个问题 ...";
            break;
        default:
        	msg = "程序发生未知错误";
    }
    return msg;
}

    // Add a new test
    function addTest() {
        divTest = document.createElement("tbody");               // New Test container
        divTest.setAttribute("id","test"+id);
        divTest.appendChild(createTitle());                      // New title row
        divTest.appendChild(createBoxes());                      // New boxes row
        document.getElementById("tests").appendChild(divTest);
        id++;
    }

    // Delete an existing test
    function delTest(testId) {
        var divTest = document.getElementById("test"+testId);
        document.getElementById("tests").removeChild(divTest);
        id--;
    }

    // Create test title (Test+id)
    function createTitle() {
        
        trTitle = document.createElement("tr");         // New title row
        tdTitle = document.createElement("td");         // New title cell       
        aDel = document.createElement("a");             // New link 'Delete test'
        imgDel = document.createElement("img");         // New image 'Delete test'
        txtTest = document.createElement("strong")      // New Test text

        
        trTitle.setAttribute("valign","top");                                   // Set attributes to title row
        trTitle.setAttribute("style","border-bottom: 1px solid #BBBBBB;");      // Set attributes to title row
        tdTitle.setAttribute("colspan","2");                                    // Set attributes to title cell
        aDel.setAttribute("href","#");                                          // Set attributes to link
        aDel.setAttribute("onclick","Javascript:delTest("+id+");");                 // Set attributes to link
        imgDel.setAttribute("src",pixpath+"/t/switch_minus.gif");    // Set attributes to image

        // Title structure
        txtTest.appendChild(document.createTextNode(" Test"));
        aDel.appendChild(imgDel);
        tdTitle.appendChild(aDel);
        tdTitle.appendChild(txtTest);
        trTitle.appendChild(tdTitle);

        return trTitle;
    }

    // Create input / output boxes
    function createBoxes() {
        
        tr = document.createElement("tr");          // New row
        tdIn = document.createElement("td");        // New 'input' cell 
        tdOut = document.createElement("td");       // New 'output' cell
        tbIn = document.createElement("input");     // New 'input' textBox
        tbOut = document.createElement("input");    // New 'output' textBox
        txtIn = document.createElement("strong")    // New input text
        txtOut = document.createElement("strong")   // New output text
        
        tdIn.setAttribute("align","right");         // Set attributes to cell
        
        tbIn.setAttribute("type","text");           // Set attributes to textBox 'input'
        tbIn.setAttribute("name","input[]");        // Set attributes to textBox 'input'
        tbIn.setAttribute("size","30");             // Set attributes to textBox 'input'
        
        tbOut.setAttribute("type","text");          // Set attributes to textBox 'output'
        tbOut.setAttribute("name","output[]");      // Set attributes to textBox 'output'
        tbOut.setAttribute("size","30");            // Set attributes to textBox 'output'
        tbIn.focus();
        
        
        // Row structure
        txtIn.appendChild(document.createTextNode("Input: "));
        txtOut.appendChild(document.createTextNode("Output: "));
        tdIn.appendChild(txtIn);
        tdIn.appendChild(tbIn);
        tdOut.appendChild(txtOut);
        tdOut.appendChild(tbOut);
        tr.appendChild(tdIn);
        tr.appendChild(tdOut);

        return tr;
    }
//-->