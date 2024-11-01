<?php

enum EWhereCond
{
    case EQ;
    case NEQ;
    case GT;
    case GTE;
    case LT;
    case LTE;
    case LIKE;
    case NOT_LIKE;
    case IN;
    case NOT_IN;
    case IS_NULL;
}