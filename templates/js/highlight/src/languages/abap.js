/*
Language: ABAP
Author: Laurin Pascal Kirbach <laurin.kirbach@googlemail.com>
Category: business
*/

function(hljs) {
  var KEYWORDS = {
    //TODO: edit literals 
    literal: 'abap_true abap_false abap_on abap_off COL_NEGATIVE COL_NORMAL',
    //TODO: edit keywords
    //TODO: Missing ONLY (in READ-ONLY), EXCEPTION, DISPLAY, HANDLE
    keyword:  'CLASS POOL FUNCTION INCLUDE INTERFACE PROGRAM REPORT|10 TYPE BOUND DATA ' +
              'EVENTS METHODS CONSTANTS CONTEXTS DEFINITION ENDCLASS ENDINTERFACE ENHANCEMENT ENDENHANCEMENT SECTION ' +
              'END FIELD GROUPS SYMBOLS IMPLEMENTATION INTERFACES LOCAL PARAMETERS PRIVATE PROTECTED ' +
              'PUBLIC RANGES SELECTION SCREEN SELECT OPTIONS STATIC STATICS SPOTS POOLS ' +
              'TYPES DURING OF PAGE INITIALIZATION LINE LOAD START TOP USER ' +
              'COMMAND AND ASSIGNED AT BEGIN BETWEEN BINDING BLOCK BYTE CA ' +
              'CN CO CS NA NS CALL CASE CATCH CHANGE CHECK ' +
              'CLEANUP CONTINUE CP DEFINE DO EACH ELSE ELSEIF ENDAT ENDCASE ' +
              'ENDDO ENDEXEC ENDFOR ENDFORM ENDFUNCTION ENDIF ENDLOOP ENDMETHOD ENDMODULE ENDON ' +
              'ENDPROVIDE ENDSELECT ENDTRY ENDWHILE EQ EXEC EXIT FIRST FORM GE ' +
              'GT IF IN INITIAL IS LAST LE LEAVE LOOP LT ' +
              'M METHOD MODULE NE NEW NOT NP ON OR ' +
              'PERFORM PROVIDE REQUESTED RETURN SQL STOP SUPPLIED TRANSACTION TRY WHEN ' +
              'WHILE ABS ACOS ADD CORRESPONDING ADJACENT ALIASES ALL ANALYZER ' +
              'ANY APPEND APPENDING AS ASCENDING ASIN ASSIGN ASSIGNING ASSERT ATAN ' +
              'AUTHORITY AVG BACK BADI BINARY BIT XOR BLANK BREAK POINT ' +
              'BUFFER BY CEIL CENTERED CHANGING CHARLEN CHECKBOX CLEAR CLIENT ' +
              'CLOSE CNT CODE COLLECT COLOR COMMENT COMMIT COMMUNICATION ' +
              'COMPARING COMPONENT COMPUTE CONCATENATE CONDENSE CONTROL CONTROLS CONVERT COPY COS ' +
              'COSH COUNT COUNTRY CREATE CURRENCY CURSOR CUSTOMER DATABASE DATASET DATE ' +
              'DBMAXLEN DECIMALS DEFAULT DELETE DEMAND DESCENDING DESCRIBE DETAIL DIALOG TINCT ' +
              'DIV DIVIDE DUPLICATES DYNPRO EDIT EDITOR ENCODING EQUAL EVENT EXCEPTIONS ' +
              'EXCLUDING EXP EXPONENT EXPORT EXPORTING EXTENDED EXTRACT FETCH FIELDS FIND ' +
              'FLOOR FOR FORMAT FRAC FRAME FREE FROM GENERATE GET GREATER ' +
              'GROUP HANDLER HASHED HEADER HELP ID REQUEST HIDE HOTSPOT ' +
              'ICON IMPORT IMPORTING INDEX INFOTYPES INHERITING INPUT INSERT INTENSIFIED INTO ' +
              'INVERSE ITERATOR JOIN KEY LANGUAGE LEADING LEFT JUSTIFIED LESS LIKE ' +
              'LINES SIZE LIST PROCESSING LOWER LOCALE LOG LOG10 MARGIN MASK ' +
              'MATCHCODE MAX MEMORY MESH MESSAGE MIN MOD MODE MODIFY MOVE ' +
              'MULTIPLY NEXT NO NODES GAP HEADING NON UNIQUE SCROLLING SIGN ' +
              'TITLE ZERO NUMBER NUMOFCHAR OCCURS OCCURRENCES OPEN OPTIONAL ORDER OTHERS ' +
              'OUTPUT OVERLAY PACK PARAMETER PF STATUS PLACES POSITION PRINT PROPERTY ' +
              'PUT RADIOBUTTON RAISE RAISING READ RECEIVE REDEFINITION REF REFERENCE REFRESH ' +
              'REJECT REPLACE RESERVE RESET RIGHT ROLLBACK ROUND ROWS RTTI RUN ' +
              'SCAN SCROLL BOUNDARY SEARCH TABLE SELECTOR SEPARATED SET SHARED SHIFT ' +
              'SIN SINGLE SINH SKIP SORT SORTED SPECIFIED SPLIT SQRT STAMP ' +
              'STANDARD STARTING STRUCTURE SUBMIT SUBTRACT SUM SUMMARY SUPPLY SUPPRESS ' +
              'SYMBOL SYNTAX TRACE SYSTEM TABLES TAN TANH TASK TEXT TEXTPOOL ' +
              'TIME TIMES TITLEBAR TO TRANSFER TRANSLATE TRANSPORTING TRUNC ULINE UNDER ' +
              'UNIT UNPACK UP UPDATE UPPER USING VALUE WAIT WHERE WINDOW ' +
              'WITH WORK WRITE ZONE ONLY EXCEPTION DISPLAY HANDLE',
    //TODO: edit built-in functions
    built_in: 'lines xstrlen strlen'
  };
  return {
    case_insensitive: true, //TODO: switch to `false`? Would make variables who have a keyword name appear as normal tokens 
    aliases: ['abap4'],
    keywords: KEYWORDS,
    //illegal: /\/\*/, //TODO: define other illegals
    contains: [
      //TODO: implement REGEX for ABAP-Tokens (e.g. slashes in "lw_/wfi/testcaste" get highlighted because they are operators)
      //hljs.inherit(hljs.QUOTE_STRING_MODE, {className: 'string', relevance: 0}),
      {
        className: 'string', 
        variants: [
          {begin: '`', end: '`'},
          {begin: '\'', end: '\''}
        ],
        illegal: '\\n',
        contains: []
      },
      //TODO: implement template literals here (copy python/es6?)
      {
        className: 'string', 
        begin: '\\|', end: '\\|',
        illegal: '\\n',
        contains: [{begin: '{', end: '}', relevance: 0}],
        relevance: 0
      },
      //TODO: implement operators here
      { 
        className: 'operator',
        begin: /[\?\-\+&=<>~\.\(\)\[\]@]/, //removed backslash
        contains: [{begin: '^\\*', skip: true}]
      },
      hljs.C_NUMBER_MODE, 
      hljs.COMMENT(/^\*/, '$', {relevance: 0}),
      hljs.COMMENT('"', '$', {relevance: 0})
    ]
  };
}
