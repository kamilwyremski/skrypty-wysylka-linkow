<style>
  .preloader {
    padding: 200x 0; justify-content: center; align-items:center; display: flex;
  }
  .preloader .cssload-container {
    display: block;  width: 97px;
  }
  .preloader .cssload-loading i {
    width: 19px;   height: 19px; display: inline-block;  border-radius: 50%; background: #4cbb17;
  }
  .preloader .cssload-loading i:first-child {
    opacity: 0; animation: cssload-loading-ani2 0.58s linear infinite; transform: translate(-19px);
  }
  .preloader .cssload-loading i:nth-child(2), .preloader .cssload-loading i:nth-child(3) {
    animation: cssload-loading-ani3 0.58s linear infinite;
  }
  .preloader .cssload-loading i:last-child {
    animation: cssload-loading-ani1 0.58s linear infinite;
  }
  @keyframes cssload-loading-ani1{
  100% {
    transform: translate(39px);  opacity: 0;
    }
  }
  @keyframes cssload-loading-ani2 {
  100% {
    transform: translate(19px);  opacity: 1;
    }
  }
  @keyframes cssload-loading-ani3 {
  100% {
    transform: translate(19px);
    }
  }
</style>

<div class="preloader">
  <div class="cssload-container">
    <div class="cssload-loading"><i></i><i></i><i></i><i></i></div>
  </div>
</div>