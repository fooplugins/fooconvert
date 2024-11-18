const isClickable = element => element instanceof HTMLElement && element.matches( "a,button,input,textarea,select" );

export default isClickable;